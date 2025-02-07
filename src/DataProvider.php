<?php

declare(strict_types=1);

namespace Treblle\Symfony;

use Throwable;
use function assert;
use RuntimeException;
use function is_string;
use Treblle\Model\Request;
use Treblle\Model\Response;
use Treblle\PayloadAnonymizer;
use Treblle\Contract\RequestDataProvider;
use Treblle\Contract\ResponseDataProvider;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DataProvider implements EventSubscriberInterface, RequestDataProvider, ResponseDataProvider
{
    private PayloadAnonymizer $payloadAnonymizer;

    private ?HttpResponse $httpResponse = null;

    private ?HttpRequest $httpRequest = null;

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
        if (method_exists($event, 'isMainRequest')) { // Symfony >= 5.3
            if ($event->isMainRequest()) {
                $this->httpRequest = $event->getRequest();
                $this->timestampStart = microtime(true);
            }

            return;
        }

        if (method_exists($event, 'isMainRequest')) { // Symfony < 5.3
            if ($event->isMainRequest()) {
                $this->httpRequest = $event->getRequest();
                $this->timestampStart = microtime(true);
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->httpResponse = $event->getResponse();
    }

    public function getRequest(): Request
    {
        if (null === $this->httpRequest) {
            throw new RuntimeException('No request available');
        }

        try {
            $requestData = $this->httpRequest->getContent() ?: '';
            assert(is_string($requestData));
            $requestData = json_decode($requestData, true);
            $requestBody = $this->payloadAnonymizer->annonymize($requestData);
        } catch (Throwable $throwable) {
            $requestBody = [];
        }

        $requestParams = array_merge(
            $this->httpRequest->request->all(),
            $this->httpRequest->query->all(),
        );

        return new Request(
            gmdate('Y-m-d H:i:s'),
            $this->httpRequest->getClientIp() ?: 'unknown',
            $this->httpRequest->getUri(),
            $this->httpRequest->headers->get('USER-AGENT', 'unknown') ?: 'unknown',
            $this->httpRequest->getMethod(),
            $this->normalizeHeaders($this->httpRequest->headers->all()),
            $this->payloadAnonymizer->annonymize($requestParams),
            $requestBody
        );
    }

    public function getResponse(): Response
    {
        if (null === $this->httpResponse) {
            throw new RuntimeException('No response available');
        }

        $responseSize = 0;

        try {
            $content = $this->httpResponse->getContent() ?: '';
            $responseSize = mb_strlen($content);
            $responseBody = json_decode($content, true);
            $responseBody = $this->payloadAnonymizer->annonymize($responseBody);
        } catch (Throwable $throwable) {
            $responseBody = [];
        }

        $time = (float) microtime(true) - $this->timestampStart;

        return new Response(
            $this->normalizeHeaders($this->httpResponse->headers->all()),
            $this->httpResponse->getStatusCode(),
            $responseSize,
            $time,
            $responseBody,
        );
    }

    /**
     * @param array<string, array<string>> $allHeaders
     *
     * @return array<string, string>
     */
    private function normalizeHeaders(array $allHeaders): array
    {
        $headers = [];
        foreach ($allHeaders as $name => $value) {
            $headers[$name] = implode(', ', $value);
        }

        return $headers;
    }
}
