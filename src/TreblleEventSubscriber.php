<?php

declare(strict_types=1);

namespace Treblle\Symfony;

use Throwable;
use Treblle\Php\Factory\TreblleFactory;
use Treblle\Php\DataTransferObject\Error;
use Treblle\Php\InMemoryErrorDataProvider;
use Treblle\Php\Contract\ErrorDataProvider;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Treblle\Symfony\DataProviders\SymfonyRequestDataProvider;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Treblle\Symfony\DataProviders\SymfonyResponseDataProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TreblleEventSubscriber implements EventSubscriberInterface
{
    private HttpRequest $request;

    private HttpResponse $response;

    private ErrorDataProvider $errorDataProvider;

    public function __construct(
        private readonly TreblleConfiguration $configuration,
        private readonly RouterInterface $router
    ) {
        $this->errorDataProvider = new InMemoryErrorDataProvider();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (
            method_exists($event, 'isMainRequest') &&
            $event->isMainRequest()
        ) {
            $request = $event->getRequest();
            $this->request = $request;
            $request->attributes->set('treblle_request_started_at', microtime(true));
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->request = $event->getRequest();
        $this->response = $event->getResponse();
    }

    /**
     * @throws Throwable
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->errorDataProvider->addError(new Error(
            message: $exception->getMessage(),
            file: $exception->getFile(),
            line: $exception->getLine(),
            source: 'onException',
            type: 'UNHANDLED_EXCEPTION',
        ));

        // When exception happens,
        // response and terminate handlers are not executed
        // hence, we don't have access to response
        $this->request = $event->getRequest();
        $this->response = new HttpResponse(status: HttpResponse::HTTP_INTERNAL_SERVER_ERROR);

        $this->onKernelTerminate($event);
    }

    /**
     * @throws Throwable
     */
    public function onKernelTerminate(KernelEvent $event): void
    {
        $ignoredEnvironments = array_map('trim', explode(',', $this->configuration->getIgnoredEnvironments()));
        $appEnvironment = $_ENV['APP_ENV'];

        if (in_array($appEnvironment, $ignoredEnvironments)) {
            return;
        }

        $routePath = $this->router->getRouteCollection()->get($this->request->attributes->get('_route'))->getPath();
        $requestProvider = new SymfonyRequestDataProvider($this->configuration, $this->request, $routePath);
        $responseProvider = new SymfonyResponseDataProvider($this->configuration, $this->request, $this->response, $this->errorDataProvider);

        $treblle = TreblleFactory::create(
            apiKey: $this->configuration->getApiKey(),
            projectId: $this->configuration->getProjectId(),
            debug: $this->configuration->isDebug(),
            maskedFields: $this->configuration->getMaskedFields(),
            config: [
                'url' => $this->configuration->getUrl(),
                'register_handlers' => false,
                'fork_process' => false,
                'request_provider' => $requestProvider,
                'response_provider' => $responseProvider,
                'error_provider' => $this->errorDataProvider,
            ]
        );

        // Manually execute onShutdown because on octane server never shuts down
        // so registered shutdown function never gets called
        // hence we have disabled handlers using config register_handlers
        $treblle
            ->setName(TreblleBundle::SDK_NAME)
            ->setVersion(TreblleBundle::SDK_VERSION)
            ->onShutdown();
    }
}
