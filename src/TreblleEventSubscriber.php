<?php

declare(strict_types=1);

namespace Treblle\Symfony;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;
use Treblle\Symfony\Doctrine\QueryCollector;
use Treblle\Symfony\Http\TreblleClientInterface;
use Treblle\Symfony\Messenger\SendTrebllePayload;
use Treblle\Symfony\Payload\PayloadBuilder;
use Treblle\Symfony\Routing\PathMatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final class TreblleEventSubscriber implements EventSubscriberInterface
{
    private ?Request $request = null;

    private ?Response $response = null;

    private array $errors = [];

    /** @var object|null Symfony\Component\Messenger\MessageBusInterface */
    private ?object $messageBus = null;

    public function __construct(
        private readonly TreblleConfiguration $configuration,
        private readonly TreblleClientInterface $client,
        private readonly PayloadBuilder $payloadBuilder,
        private readonly PathMatcher $pathMatcher,
        private readonly RouterInterface $router,
        private readonly QueryCollector $queryCollector,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function setMessageBus(object $messageBus): void
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 4096],
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        try {
            if (! $event->isMainRequest()) {
                return;
            }

            $request = $event->getRequest();
            $request->attributes->set('treblle_request_started_at', microtime(true));
            $this->request = $request;
        } catch (Throwable $e) {
            $this->logger->warning('Failed to capture request.', ['exception' => $e->getMessage()]);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        try {
            if (! $event->isMainRequest()) {
                return;
            }

            $this->request = $event->getRequest();
            $this->response = $event->getResponse();
        } catch (Throwable $e) {
            $this->logger->warning('Failed to capture response.', ['exception' => $e->getMessage()]);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        try {
            $throwable = $event->getThrowable();

            $this->errors[] = [
                'source' => 'onException',
                'type' => 'UNHANDLED_EXCEPTION',
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ];

            $this->request = $event->getRequest();

            if ($this->response === null) {
                $this->response = new Response(status: Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (Throwable $e) {
            $this->logger->warning('Failed to record exception.', ['exception' => $e->getMessage()]);
        }
    }

    public function onKernelTerminate(KernelEvent $event): void
    {
        try {
            $this->process();
        } catch (Throwable $e) {
            $this->logger->warning('Unexpected error in terminate handler.', ['exception' => $e->getMessage()]);
        } finally {
            $this->reset();
        }
    }

    private function process(): void
    {
        if (! $this->configuration->isEnabled()) {
            $this->logger->debug('Treblle is disabled via configuration.');

            return;
        }

        if ($this->request === null || $this->response === null) {
            $this->logger->debug('Skipping — request or response not captured.');

            return;
        }

        $path = $this->request->getPathInfo();

        if ($this->pathMatcher->isExcluded($path, $this->configuration->getExcludedPaths())) {
            $this->logger->debug('Skipping excluded path.', ['path' => $path]);

            return;
        }

        $sdkToken = $this->configuration->getSdkToken();
        $apiKey = $this->configuration->getApiKey();

        if ($sdkToken === '') {
            $this->logger->warning('SDK Token is missing. Set treblle.sdk_token in your configuration.');

            return;
        }

        if ($apiKey === '') {
            $this->logger->warning('API Key is missing. Set treblle.api_key in your configuration.');

            return;
        }

        $requestStartedAt = (float) $this->request->attributes->get(
            'treblle_request_started_at',
            microtime(true)
        );

        $routePath = $this->resolveRoutePath();

        $payload = $this->payloadBuilder->build(
            request: $this->request,
            response: $this->response,
            requestStartedAt: $requestStartedAt,
            routePath: $routePath,
            errors: $this->errors,
        );

        if ($this->configuration->isAsync() && $this->messageBus !== null) {
            $this->logger->debug('Dispatching payload async via Messenger.', ['path' => $path]);
            $this->messageBus->dispatch(new SendTrebllePayload($payload));
        } else {
            $this->logger->debug('Sending payload.', ['path' => $path]);
            $this->client->send($payload);
        }
    }

    private function resolveRoutePath(): ?string
    {
        try {
            $routeName = $this->request?->attributes->get('_route');

            if (! is_string($routeName) || $routeName === '') {
                return null;
            }

            return $this->router->getRouteCollection()->get($routeName)?->getPath();
        } catch (Throwable) {
            return null;
        }
    }

    private function reset(): void
    {
        $this->request = null;
        $this->response = null;
        $this->errors = [];
        $this->queryCollector->reset();
    }

}
