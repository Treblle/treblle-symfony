<?php

declare(strict_types=1);

namespace Treblle\Symfony;

use Throwable;
use RuntimeException;
use Treblle\Php\FieldMasker;
use Treblle\Php\DataTransferObject\Request;
use Treblle\Php\DataTransferObject\Response;
use Treblle\Php\Contract\RequestDataProvider;
use Symfony\Component\HttpKernel\KernelEvents;
use Treblle\Php\Contract\ResponseDataProvider;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DataProvider implements EventSubscriberInterface, RequestDataProvider, ResponseDataProvider
{
    private ?HttpResponse $httpResponse = null;

    private ?HttpRequest $httpRequest = null;

    private float $timestampStart = 0;

    public function __construct(private FieldMasker $fieldMasker)
    {
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
        if (
            method_exists($event, 'isMainRequest') &&
            $event->isMainRequest()
        ) {
            $this->httpRequest = $event->getRequest();
            $this->timestampStart = microtime(true);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->httpResponse = $event->getResponse();
    }

    public function getRequest(): Request
    {
        // TODO: By default it should be invoked, remove once confirmed
        if (null === $this->httpRequest) {
            throw new RuntimeException('No request available');
        }

        // TODO: Why we are getting request content here ?
        //        try {
        //            $requestData = $this->httpRequest->getContent() ?: '';
        //            $requestData = json_decode($requestData, true);
        //            $requestBody = $this->fieldMasker->mask($requestData);
        //        } catch (Throwable $throwable) {
        //            $requestBody = [];
        //        }

        // TODO: verify what is returned in request all does it includes query already
        //        $requestParams = array_merge(
        //            $this->httpRequest->request->all(),
        //            $this->httpRequest->query->all(),
        //        );

        return new Request(
            timestamp: gmdate('Y-m-d H:i:s'),
            url: $this->httpRequest->getUri(),
            ip: $this->httpRequest->getClientIp() ?: 'bogon',
            user_agent: $this->httpRequest->headers->get('USER-AGENT', '') ?: '',
            method: $this->httpRequest->getMethod(),
            headers: $this->fieldMasker->mask($this->normalizeHeaders($this->httpRequest->headers->all())),
            query: $this->fieldMasker->mask($this->httpRequest->query->all()),
            body: $this->fieldMasker->mask($this->httpRequest->request->all()),
            route_path: $this->httpRequest->attributes->get('_route')?->getPath() ?? null,
        );
    }

    public function getResponse(): Response
    {
        // TODO: By default it should be invoked, remove once confirmed
        if (null === $this->httpResponse) {
            throw new RuntimeException('No response available');
        }

        $responseSize = 0;

        try {
            $content = $this->httpResponse->getContent() ?: '';
            $responseSize = mb_strlen($content);
            $responseBody = json_decode($content, true);
            $responseBody = $this->fieldMasker->mask($responseBody);
        } catch (Throwable $throwable) {
            $responseBody = [];
        }

        // converting to milliseconds
        $time = (float) (microtime(true) * 1000) - ($this->timestampStart * 1000);

        return new Response(
            code: $this->httpResponse->getStatusCode(),
            size: $responseSize,
            load_time: $time,
            body: $this->fieldMasker->mask($responseBody),
            headers: $this->fieldMasker->mask($this->normalizeHeaders($this->httpRequest->headers->all())),
        );
    }

    /**
     * @param  array<string, array<string>>  $allHeaders
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
