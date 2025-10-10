<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

final readonly class TreblleConfiguration
{
    /**
     * @param array<int,string> $maskedFields
     * @param array<int,string> $excludedHeaders
     */
    public function __construct(
        private string  $apiKey,
        private string  $sdkToken,
        private ?string $url = null,
        private string  $ignoredEnvironments = 'dev,test,testing',
        private array   $maskedFields = [],
        private array   $excludedHeaders = [],
        private bool    $debug = false,
    ) {
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getSdkToken(): string
    {
        return $this->sdkToken;
    }

    public function getIgnoredEnvironments(): string
    {
        return $this->ignoredEnvironments;
    }

    public function getMaskedFields(): array
    {
        return $this->maskedFields;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getExcludedHeaders(): array
    {
        return $this->excludedHeaders;
    }
}
