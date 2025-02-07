<?php

declare(strict_types=1);

namespace Treblle\Symfony\EventSubscriber;

use Throwable;
use Treblle\Treblle;
use function in_array;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::TERMINATE => 'onKernelTerminate',
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onKernelRequest(KernelEvent $event): void
    {
        $request = $event->getRequest();
        $requestId = $request->headers->get('X-TREBLLE-TRACE-ID', uniqid('req_', true));
        $request->attributes->set('requestId', $requestId);
    }

    public function onKernelTerminate(KernelEvent $event): void
    {
        if (in_array($event->getRequest()->getRequestUri(), $this->treblle->ignoredUris(), true)) {
            return;
        }

        try {
            $this->treblle->onShutdown();
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }
    }

    public function onException(KernelEvent $event): void
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
