<?php

declare(strict_types=1);

namespace Treblle\Symfony\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Treblle\Symfony\Doctrine\QueryCollector;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;
use Treblle\Symfony\Http\TreblleClientInterface;
use Treblle\Symfony\Masking\DataMasker;
use Treblle\Symfony\Payload\PayloadBuilder;
use Treblle\Symfony\Routing\PathMatcher;
use Treblle\Symfony\TreblleEventSubscriber;

final class TreblleEventSubscriberTest extends TestCase
{
    private HttpKernelInterface&MockObject $kernel;
    private TreblleClientInterface&MockObject $client;
    private RouterInterface&MockObject $router;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(HttpKernelInterface::class);
        $this->client = $this->createMock(TreblleClientInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->router->method('getRouteCollection')->willReturn(new RouteCollection());
    }

    public function test_subscribes_to_required_kernel_events(): void
    {
        $events = TreblleEventSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertArrayHasKey(KernelEvents::TERMINATE, $events);
    }

    public function test_on_request_sets_start_timestamp_for_main_request(): void
    {
        $subscriber = $this->makeSubscriber();
        $request = Request::create('/api/users', 'GET');

        $subscriber->onKernelRequest(new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $this->assertTrue($request->attributes->has('treblle_request_started_at'));
    }

    public function test_on_request_ignores_sub_requests(): void
    {
        $subscriber = $this->makeSubscriber();
        $request = Request::create('/api/users', 'GET');

        $subscriber->onKernelRequest(new RequestEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST));

        $this->assertFalse($request->attributes->has('treblle_request_started_at'));
    }

    public function test_on_exception_payload_is_still_sent(): void
    {
        $subscriber = $this->makeSubscriber();
        $request = Request::create('/api/users', 'GET');

        $this->client->expects($this->once())->method('send');

        $subscriber->onKernelRequest(new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $subscriber->onKernelException(new ExceptionEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, new \RuntimeException('Boom')));
        $subscriber->onKernelTerminate(new KernelEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST));
    }

    public function test_process_skips_when_disabled(): void
    {
        $subscriber = $this->makeSubscriber(enabled: false);

        $this->client->expects($this->never())->method('send');

        $this->simulateFullRequest($subscriber, Request::create('/api/users', 'GET'));
    }

    public function test_process_skips_excluded_path(): void
    {
        $subscriber = $this->makeSubscriber(excludedPaths: ['/health']);

        $this->client->expects($this->never())->method('send');

        $this->simulateFullRequest($subscriber, Request::create('/health', 'GET'));
    }

    public function test_process_skips_when_sdk_token_missing(): void
    {
        $subscriber = $this->makeSubscriber(sdkToken: '');

        $this->client->expects($this->never())->method('send');

        $this->simulateFullRequest($subscriber, Request::create('/api/users', 'GET'));
    }

    public function test_process_skips_when_api_key_missing(): void
    {
        $subscriber = $this->makeSubscriber(apiKey: '');

        $this->client->expects($this->never())->method('send');

        $this->simulateFullRequest($subscriber, Request::create('/api/users', 'GET'));
    }

    public function test_process_sends_payload_when_all_conditions_met(): void
    {
        $subscriber = $this->makeSubscriber();

        $this->client->expects($this->once())->method('send');

        $this->simulateFullRequest($subscriber, Request::create('/api/users', 'GET'));
    }

    private function makeSubscriber(
        string $sdkToken = 'sdk-token',
        string $apiKey = 'api-key',
        bool $enabled = true,
        array $excludedPaths = [],
    ): TreblleEventSubscriber {
        $config = new TreblleConfiguration(
            sdkToken: $sdkToken,
            apiKey: $apiKey,
            enabled: $enabled,
            excludedPaths: $excludedPaths,
        );

        $queryCollector = new QueryCollector();

        return new TreblleEventSubscriber(
            configuration: $config,
            client: $this->client,
            payloadBuilder: new PayloadBuilder($config, new DataMasker(), $queryCollector),
            pathMatcher: new PathMatcher(),
            router: $this->router,
            queryCollector: $queryCollector,
        );
    }

    private function simulateFullRequest(TreblleEventSubscriber $subscriber, Request $request): void
    {
        $response = new Response('{}', 200, ['Content-Type' => 'application/json']);

        $subscriber->onKernelRequest(new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $subscriber->onKernelResponse(new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));
        $subscriber->onKernelTerminate(new KernelEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST));
    }
}
