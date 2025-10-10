<?php

declare(strict_types=1);

namespace Treblle\Symfony\DataProviders;

use Throwable;
use Treblle\Php\DataTransferObject\Request;
use Treblle\Php\Helpers\SensitiveDataMasker;
use Treblle\Php\Contract\RequestDataProvider;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;

final readonly class SymfonyRequestDataProvider implements RequestDataProvider
{
    public function __construct(
        private TreblleConfiguration $configuration,
        private HttpRequest          $request,
        private ?string $routePath = null,
    ) {
    }

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
        $headers = [];
        foreach ($this->request->headers->all() as $name => $value) {
            $headers[$name] = implode(', ', $value);
        }

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
