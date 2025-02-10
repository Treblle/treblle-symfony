<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

final readonly class TreblleConfiguration
{
    /**
     * @param array<int,string> $maskedFields
     */
    public function __construct(
        private string  $apiKey,
        private string  $projectId,
        private ?string $url = null,
        private string  $ignoredEnvironments = 'dev,test,testing',
        private array   $maskedFields = [],
        private bool    $debug = false,
    ) {
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getProjectId(): string
    {
        return $this->projectId;
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

    public function getUrl(): string
    {
        return $this->url;
    }
}
