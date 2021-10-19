<?php

declare(strict_types=1);

namespace Treblle\Symfony\DataProvider;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Treblle\Model\Response;
use Treblle\PayloadAnonymizer;

final class ResponseDataProvider implements \Treblle\Contract\ResponseDataProvider, EventSubscriberInterface
{
    private PayloadAnonymizer $payloadAnonymizer;
    private ?HttpResponse $httpResponse = null;
    private float $timestampStart = 0;

    public function __construct(PayloadAnonymizer $payloadAnonymizer)
    {
        $this->payloadAnonymizer = $payloadAnonymizer;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMasterRequest()) {
            $this->timestampStart = microtime(true);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->httpResponse = $event->getResponse();
    }

    public function getResponse(): Response
    {
        if (null === $this->httpResponse) {
            throw new \RuntimeException('No response available');
        }

        $responseSize = 0;
        try {
            $content = $this->httpResponse->getContent();
            $responseSize = mb_strlen($content);
            $responseBody = \Safe\json_decode($content, true);
            $responseBody = $this->payloadAnonymizer->annonymize($responseBody);
        } catch (\Throwable $throwable) {
            $responseBody = [];
        }

        $time = (float) microtime(true) - $this->timestampStart;

        return new Response(
            $this->httpResponse->headers->all(),
            $this->httpResponse->getStatusCode(),
            $responseSize,
            $time,
            $responseBody,
        );
    }
}
