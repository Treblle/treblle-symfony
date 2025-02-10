<?php

declare(strict_types=1);

namespace Treblle\Symfony\DataProviders;

use Treblle\Php\FieldMasker;
use Treblle\Symfony\Helpers\Normalise;
use Treblle\Php\DataTransferObject\Request;
use Treblle\Php\Contract\RequestDataProvider;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;

final readonly class SymfonyRequestDataProvider implements RequestDataProvider
{
    public function __construct(
        private HttpRequest          $request,
        private TreblleConfiguration $configuration,
    ) {
    }

    public function getRequest(): Request
    {
        $fieldMasker = new FieldMasker($this->configuration->getMaskedFields());

        return new Request(
            timestamp: gmdate('Y-m-d H:i:s'),
            url: $this->request->getUri(),
            ip: $this->request->getClientIp() ?: 'bogon',
            user_agent: $this->request->headers->get('USER-AGENT', '') ?: '',
            method: $this->request->getMethod(),
            headers: $fieldMasker->mask(Normalise::headers($this->request->headers->all())),
            query: $fieldMasker->mask($this->request->query->all()),
            body: $fieldMasker->mask($this->request->request->all()),
            route_path: $this->request->attributes->get('_route')?->getPath() ?? null,
        );
    }
}
