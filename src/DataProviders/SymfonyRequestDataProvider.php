<?php

declare(strict_types=1);

namespace Treblle\Symfony\DataProviders;

use Throwable;
use Treblle\Php\DataTransferObject\Request;
use Treblle\Php\Helpers\SensitiveDataMasker;
use Treblle\Php\Contract\RequestDataProvider;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;

/**
 * SymfonyRequestDataProvider extracts and formats request data from Symfony HTTP requests.
 *
 * This provider implements the treblle-php RequestDataProvider contract to convert
 * Symfony Request objects into Treblle's Request DTO format. It handles:
 * - Request URL, method, timestamp, and client IP
 * - Headers, query parameters, and request body
 * - Sensitive data masking for headers, query, and body
 * - Route path extraction for better API documentation
 * - Header normalization from array to string format
 *
 * @see RequestDataProvider For the interface contract
 * @see Request For the Treblle Request DTO
 */
final readonly class SymfonyRequestDataProvider implements RequestDataProvider
{
    /**
     * Creates a new SymfonyRequestDataProvider instance.
     *
     * @param TreblleConfiguration $configuration Configuration for masking and settings
     * @param HttpRequest $request The HTTP request to extract data from
     * @param string|null $routePath Optional route path from router (e.g., /api/users/{id})
     */
    public function __construct(
        private TreblleConfiguration $configuration,
        private HttpRequest          $request,
        private ?string $routePath = null,
    ) {
    }

    /**
     * Extracts and returns the request data as a Treblle Request DTO.
     *
     * This method:
     * - Extracts request metadata (timestamp, URL, IP, user agent, method)
     * - Parses and decodes JSON request body
     * - Normalizes headers from array to string format
     * - Merges body and query parameters
     * - Masks sensitive fields in headers, query, and body
     * - Includes the route path for API documentation
     *
     * @return Request The formatted request data
     */
    public function getRequest(): Request
    {
        $masker = new SensitiveDataMasker($this->configuration->getMaskedFields());
        $query = $this->request->query->all();

        try {
            $body = $this->request->getContent() ?: '{}';
            $body = json_decode($body, true);
        } catch (Throwable $throwable) {
            $body = [];
        }

        // Normalize headers from array format to string format
        $headers = array_map(fn ($value) => is_array($value) ? implode(', ', $value) : $value, $this->response->headers->all());

        return new Request(
            timestamp: gmdate('Y-m-d H:i:s'),
            url: $this->request->getUri(),
            ip: $this->request->getClientIp() ?: 'bogon',
            user_agent: $this->request->headers->get('USER-AGENT', '') ?: '',
            method: $this->request->getMethod(),
            headers: $masker->mask($headers),
            query: $masker->mask($query),
            body: $masker->mask(array_merge($body, $query)),
            route_path: $this->routePath,
        );
    }
}
