<?php

declare(strict_types=1);

namespace Treblle\Symfony\EventSubscriber;

use Throwable;
use Treblle\Php\Treblle;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TreblleEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Treblle         $treblle,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::TERMINATE => 'onKernelTerminate',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelRequest(KernelEvent $event): void
    {
        $request = $event->getRequest();
        $request->attributes->set('treblle_request_started_at', microtime(true));
    }

    public function onKernelTerminate(KernelEvent $event): void
    {
        try {
            $this->treblle->onShutdown();
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }
    }

    public function onKernelException(KernelEvent $event): void
    {
        if (! $event instanceof ExceptionEvent) {
            return;
        }

        try {
            $throwable = $event->getThrowable();
            $this->treblle->onException($throwable);
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }
    }
}
