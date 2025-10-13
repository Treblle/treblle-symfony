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

/**
 * SymfonyResponseDataProvider extracts and formats response data from Symfony HTTP responses.
 *
 * This provider implements the treblle-php ResponseDataProvider contract to convert
 * Symfony Response objects into Treblle's Response DTO format. It handles:
 * - Status codes and headers
 * - Response body with JSON decoding
 * - Response size calculation
 * - Load time measurement
 * - Sensitive data masking
 * - Large response handling (>2MB)
 *
 * @see ResponseDataProvider For the interface contract
 * @see Response For the Treblle Response DTO
 */
final readonly class SymfonyResponseDataProvider implements ResponseDataProvider
{
    /**
     * Creates a new SymfonyResponseDataProvider instance.
     *
     * @param TreblleConfiguration $configuration Configuration for masking and settings
     * @param HttpRequest $request The HTTP request (used for load time calculation)
     * @param HttpResponse $response The HTTP response to extract data from
     * @param ErrorDataProvider &$errorDataProvider Error provider for tracking issues (passed by reference)
     */
    public function __construct(
        private TreblleConfiguration $configuration,
        private HttpRequest          $request,
        private HttpResponse         $response,
        private ErrorDataProvider    &$errorDataProvider,
    ) {
    }

    /**
     * Extracts and returns the response data as a Treblle Response DTO.
     *
     * This method:
     * - Extracts response body, status code, and headers
     * - Normalizes headers from array to string format
     * - Calculates response size and load time
     * - Masks sensitive fields in headers and body
     * - Handles large responses (>2MB) by truncating body
     *
     * @return Response The formatted response data
     */
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
        $headers = array_map(fn ($value) => is_array($value) ? implode(', ', $value) : $value, $this->response->headers->all());

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

    /**
     * Calculates the request load time in milliseconds.
     *
     * This method calculates the time between when the request started and now.
     * It prioritizes the 'treblle_request_started_at' attribute set in onKernelRequest,
     * falling back to $_SERVER['REQUEST_TIME_FLOAT'] if the attribute is not available.
     *
     * @return float The load time in milliseconds
     */
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
