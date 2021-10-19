<?php

declare(strict_types=1);

namespace Treblle\Symfony\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Treblle\Treblle;

final class TreblleEventSubscriber implements EventSubscriberInterface
{
    private Treblle $treblle;

    public function __construct(Treblle $treblle)
    {
        $this->treblle = $treblle;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->treblle->onShutdown();
    }

    public function onException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $this->treblle->onException($throwable);
    }
}
