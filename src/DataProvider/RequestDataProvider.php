<?php

declare(strict_types=1);

namespace Treblle\Symfony\DataProvider;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Treblle\Model\Request;
use Treblle\PayloadAnonymizer;

final class RequestDataProvider implements \Treblle\Contract\RequestDataProvider, EventSubscriberInterface
{
    private PayloadAnonymizer $payloadAnonymizer;
    private ?HttpRequest $httpRequest = null;

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
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMasterRequest()) {
            $this->httpRequest = $event->getRequest();
        }
    }

    public function getRequest(): Request
    {
        if (null === $this->httpRequest) {
            throw new \RuntimeException('No request available');
        }

        try {
            $requestBody = $this->httpRequest->toArray();
            $requestBody = $this->payloadAnonymizer->annonymize($requestBody);
        } catch (\Throwable $throwable) {
            $requestBody = [];
        }

        $requestParams = array_merge(
            $this->httpRequest->request->all(),
            $this->httpRequest->query->all(),
            $this->httpRequest->attributes->all(),
        );

        return new Request(
            \Safe\gmdate('Y-m-d H:i:s'),
            $this->httpRequest->getClientIp(),
            $this->httpRequest->getBasePath(),
            $this->httpRequest->headers->get('User-agent', 'unknown'),
            $this->httpRequest->getMethod(),
            $this->httpRequest->headers->all(),
            $this->payloadAnonymizer->annonymize($requestParams),
            $requestBody
        );
    }
}
