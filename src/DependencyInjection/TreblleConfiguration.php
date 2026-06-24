<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

final readonly class TreblleConfiguration
{
    public function __construct(
        private string $sdkToken,
        private string $apiKey,
        private bool $enabled = true,
        private array $maskedKeywords = [],
        private array $excludedPaths = [],
        private string $ingressUrl = 'https://ingress.treblle.com',
        private bool $async = false,
        private array $metadata = [],
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getSdkToken(): string
    {
        return $this->sdkToken;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getMaskedKeywords(): array
    {
        return $this->maskedKeywords;
    }

    public function getExcludedPaths(): array
    {
        return $this->excludedPaths;
    }

    public function getIngressUrl(): string
    {
        return $this->ingressUrl;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
