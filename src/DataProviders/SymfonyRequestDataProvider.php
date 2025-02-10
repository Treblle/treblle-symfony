<?php

declare(strict_types=1);

namespace Treblle\Symfony\DataProviders;

use Throwable;
use Treblle\Php\FieldMasker;
use Treblle\Symfony\Helpers\Normalise;
use Treblle\Php\DataTransferObject\Request;
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
        $fieldMasker = new FieldMasker($this->configuration->getMaskedFields());
        $query = $this->request->query->all();

        try {
            $body = $this->request->getContent() ?: '';
            $body = json_decode($body, true);
        } catch (Throwable $throwable) {
            $body = [];
        }

        return new Request(
            timestamp: gmdate('Y-m-d H:i:s'),
            url: $this->request->getUri(),
            ip: $this->request->getClientIp() ?: 'bogon',
            user_agent: $this->request->headers->get('USER-AGENT', '') ?: '',
            method: $this->request->getMethod(),
            headers: $fieldMasker->mask(Normalise::headers($this->request->headers->all())),
            query: $fieldMasker->mask($query),
            body: $fieldMasker->mask(array_merge($body, $query)),
            route_path: $this->routePath,
        );
    }
}
