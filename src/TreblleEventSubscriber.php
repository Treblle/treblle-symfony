<?php

declare(strict_types=1);

namespace Treblle\Symfony;

use Throwable;
use Treblle\Php\Factory\TreblleFactory;
use Treblle\Php\DataTransferObject\Error;
use Treblle\Php\Contract\ErrorDataProvider;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Treblle\Symfony\Exceptions\TreblleException;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Treblle\Php\DataProviders\InMemoryErrorDataProvider;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Treblle\Symfony\DataProviders\SymfonyRequestDataProvider;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Treblle\Symfony\DataProviders\SymfonyResponseDataProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * TreblleEventSubscriber is the main event subscriber that captures API requests and sends them to Treblle.
 *
 * This subscriber listens to kernel events throughout the request lifecycle:
 * - REQUEST: Captures the incoming request and tracks start time
 * - RESPONSE: Captures the response data
 * - EXCEPTION: Captures any unhandled exceptions that occur
 * - TERMINATE: Sends the collected data to Treblle after response is sent to client
 *
 * The subscriber integrates with the Treblle PHP SDK to send API monitoring data,
 * respecting configured masked fields, excluded headers, and ignored environments.
 *
 * @see TreblleConfiguration For configuration options
 * @see SymfonyRequestDataProvider For request data extraction
 * @see SymfonyResponseDataProvider For response data extraction
 */
final class TreblleEventSubscriber implements EventSubscriberInterface
{
    /**
     * The captured HTTP request.
     */
    private HttpRequest $request;

    /**
     * The captured HTTP response.
     */
    private HttpResponse $response;

    /**
     * Provider for collecting and storing error information.
     */
    private ErrorDataProvider $errorDataProvider;

    /**
     * Creates a new TreblleEventSubscriber instance.
     *
     * @param TreblleConfiguration $configuration The Treblle configuration
     * @param RouterInterface $router The Symfony router for route path extraction
     */
    public function __construct(
        private readonly TreblleConfiguration $configuration,
        private readonly RouterInterface $router
    ) {
        $this->errorDataProvider = new InMemoryErrorDataProvider();
    }

    /**
     * Returns an array of event names this subscriber listens to.
     *
     * Maps kernel events to their respective handler methods:
     * - REQUEST → onKernelRequest
     * - RESPONSE → onKernelResponse
     * - EXCEPTION → onKernelException
     * - TERMINATE → onKernelTerminate
     *
     * @return array<string, string> Event names mapped to handler methods
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    /**
     * Handles the kernel.request event.
     *
     * Captures the incoming HTTP request and records the request start time
     * for accurate load time calculation. Only processes main requests, not sub-requests.
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
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

    /**
     * Handles the kernel.response event.
     *
     * Captures the HTTP request and response objects for later processing
     * when sending data to Treblle.
     *
     * @param ResponseEvent $event The response event
     *
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->request = $event->getRequest();
        $this->response = $event->getResponse();
    }

    /**
     * Handles the kernel.exception event.
     *
     * Captures any unhandled exceptions that occur during request processing
     * and stores them for reporting to Treblle. Creates a 500 Internal Server Error
     * response for Treblle tracking purposes.
     *
     * @param ExceptionEvent $event The exception event
     *
     * @throws Throwable If the exception cannot be handled
     *
     * @return void
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

        $this->request = $event->getRequest();
        $this->response = new HttpResponse(status: HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Handles the kernel.terminate event.
     *
     * This is the main method that sends data to Treblle after the response has been sent to the client.
     * It performs the following steps:
     * 1. Checks if current environment should be ignored
     * 2. Validates API credentials are configured
     * 3. Extracts route path from the request
     * 4. Creates data providers for request and response
     * 5. Initializes Treblle SDK and sends the data
     *
     * This method is called after the response is sent to the client, ensuring
     * no impact on response time. It's also compatible with long-running processes
     * like Octane where shutdown handlers may not be called.
     *
     * @param KernelEvent $event The terminate event
     *
     * @throws Throwable If data cannot be sent to Treblle
     * @throws TreblleException If API key or SDK token is missing
     *
     * @return void
     */
    public function onKernelTerminate(KernelEvent $event): void
    {
        $ignoredEnvironments = array_map('trim', explode(',', $this->configuration->getIgnoredEnvironments()));
        $appEnvironment = $_ENV['APP_ENV'];

        if (in_array($appEnvironment, $ignoredEnvironments)) {
            return;
        }

        if (null === $this->configuration->getApiKey() || '' === $this->configuration->getApiKey()) {
            throw TreblleException::missingApiKey();
        }

        if (null === $this->configuration->getSdkToken() || '' === $this->configuration->getSdkToken()) {
            throw TreblleException::missingSdkToken();
        }

        $routePath = null;
        $route = $this->request->attributes->get('_route');

        if (is_string($route)) {
            $routePath = $this->router->getRouteCollection()->get($this->request->attributes->get('_route'))?->getPath();
        }

        $requestProvider = new SymfonyRequestDataProvider($this->configuration, $this->request, $routePath);
        $responseProvider = new SymfonyResponseDataProvider($this->configuration, $this->request, $this->response, $this->errorDataProvider);

        $treblle = TreblleFactory::create(
            apiKey: $this->configuration->getApiKey(),
            sdkToken: $this->configuration->getSdkToken(),
            debug: $this->configuration->isDebug(),
            maskedFields: $this->configuration->getMaskedFields(),
            excludedHeaders: $this->configuration->getExcludedHeaders(),
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
