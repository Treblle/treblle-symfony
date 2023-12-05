<?php

declare(strict_types=1);

namespace Tests\Treblle\Symfony;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Treblle\Symfony\EventSubscriber\TreblleEventSubscriber;
use Treblle\Treblle;

/**
 * @internal
 *
 * @coversNothing
 *
 * @small
 */
final class TreblleEventSubscriberTest extends TestCase
{
    private TreblleEventSubscriber $subjectUnderTest;

    /** @var MockObject&Treblle */
    private Treblle $treblle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->treblle = $this->createMock(Treblle::class);
        $this->subjectUnderTest = new TreblleEventSubscriber($this->treblle, new NullLogger());
    }

    public function test_it_is_subscribed_to_correct_events(): void
    {
        $events = TreblleEventSubscriber::getSubscribedEvents();
        $events = array_keys($events);
        $this->assertEquals([KernelEvents::TERMINATE, KernelEvents::EXCEPTION], $events);
    }

    public function test_it_calls_on_shutdown_on_kernel_terminate(): void
    {
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            $this->createMock(Response::class)
        );

        $this->treblle->expects($this->once())
            ->method('onShutdown');

        $this->subjectUnderTest->onKernelTerminate($event);
    }

    public function test_it_calls_on_exception_on_kernel_exception(): void
    {
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            HttpKernelInterface::MASTER_REQUEST,
            $this->createMock(\Throwable::class)
        );

        $this->treblle->expects($this->once())
            ->method('onException');

        $this->subjectUnderTest->onException($event);
    }
}
