<?php

declare(strict_types=1);

namespace Treblle\Symfony\DataProviders;

use Treblle\Php\DataTransferObject\Error;
use Treblle\Php\Contract\ErrorDataProvider;
use Treblle\Php\DataTransferObject\Response;
use Treblle\Php\Helpers\SensitiveDataMasker;
use Treblle\Php\Contract\ResponseDataProvider;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class SymfonyResponseDataProvider implements ResponseDataProvider
{
    public function __construct(
        private TreblleConfiguration $configuration,
        private HttpRequest          $request,
        private HttpResponse         $response,
        private ErrorDataProvider    &$errorDataProvider,
    ) {
    }

    public function getResponse(): Response
    {
        $masker = new SensitiveDataMasker($this->configuration->getMaskedFields());

        $body = $this->response->getContent();
        $size = mb_strlen($body);

        if ($size > 2 * 1024 * 1024) {
            $body = '{}';
            $size = 0;

            $this->errorDataProvider->addError(new Error(
                message: 'JSON response size is over 2MB',
                file: '',
                line: 0,
                type: 'E_USER_ERROR'
            ));
        }

        // Normalize headers from array format to string format
        $headers = [];
        foreach ($this->response->headers->all() as $name => $value) {
            $headers[$name] = implode(', ', $value);
        }

        return new Response(
            code: $this->response->getStatusCode(),
            size: $size,
            load_time: $this->getLoadTimeInMilliseconds(),
            body: $masker->mask(
                json_decode($body, true) ?? []
            ),
            headers: $masker->mask($headers),
        );
    }

    private function getLoadTimeInMilliseconds(): float
    {
        $currentTimeInMilliseconds = microtime(true) * 1000;
        $requestTimeInMilliseconds = microtime(true) * 1000;

        if ($this->request->attributes->has('treblle_request_started_at')) {
            $requestTimeInMilliseconds = $this->request->attributes->get('treblle_request_started_at') * 1000;

            return $currentTimeInMilliseconds - $requestTimeInMilliseconds;
        }

        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $requestTimeInMilliseconds = (float)$_SERVER['REQUEST_TIME_FLOAT'] * 1000;
        }

        return $currentTimeInMilliseconds - $requestTimeInMilliseconds;
    }
}
