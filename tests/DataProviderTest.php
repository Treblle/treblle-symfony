<?php

declare(strict_types=1);

namespace Tests\Treblle\Symfony;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Treblle\PayloadAnonymizer;
use Treblle\Symfony\DataProvider;

/**
 * @internal
 * @coversNothing
 *
 * @small
 */
final class DataProviderTest extends TestCase
{
    private DataProvider $subjectUnderTest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subjectUnderTest = new DataProvider(new PayloadAnonymizer([]));
    }

    public function test_it_is_subscribed_to_correct_events(): void
    {
        $events = DataProvider::getSubscribedEvents();
        $events = array_keys($events);
        $this->assertEquals([KernelEvents::REQUEST, KernelEvents::RESPONSE], $events);
    }

    public function test_it_throws_exception_when_accesing_request_without_setting_it(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No request available');
        $this->subjectUnderTest->getRequest();
    }

    public function test_it_throws_exception_when_accesing_response_without_setting_it(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No response available');
        $this->subjectUnderTest->getResponse();
    }

    public function test_it_builds_request_correctly(): void
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('isMainRequest')->willReturn(true);
        $event->expects($this->once())->method('getRequest')->willReturn(new Request());

        $this->subjectUnderTest->onKernelRequest($event);
        $request = $this->subjectUnderTest->getRequest();
        $this->assertInstanceOf(\Treblle\Model\Request::class, $request);
    }

    public function test_it_builds_response_correctly(): void
    {
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            HttpKernelInterface::MASTER_REQUEST,
            new JsonResponse(['foo' => 'bar'])
        );

        $this->subjectUnderTest->onKernelResponse($event);
        $response = $this->subjectUnderTest->getResponse();
        $this->assertInstanceOf(\Treblle\Model\Response::class, $response);
    }
}
