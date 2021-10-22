<?php

declare(strict_types=1);

namespace Treblle\Symfony\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Treblle\Treblle;

final class TreblleEventSubscriber implements EventSubscriberInterface
{
    private Treblle $treblle;
    private LoggerInterface $logger;

    public function __construct(Treblle $treblle, LoggerInterface $logger)
    {
        $this->treblle = $treblle;
        $this->logger = $logger;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onKernelTerminate(KernelEvent $event): void
    {
        try {
            $this->treblle->onShutdown();
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }
    }

    public function onException(KernelEvent $event): void
    {
        if (!$event instanceof ExceptionEvent) {
            return;
        }

        try {
            $throwable = $event->getThrowable();
            $this->treblle->onException($throwable);
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }
    }
}
