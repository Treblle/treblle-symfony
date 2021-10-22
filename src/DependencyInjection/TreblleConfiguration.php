<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

class TreblleConfiguration
{
    private string $apiKey;
    private string $projectId;
    private string $endpointUrl;
    /**
     * @var list<string>
     */
    private array $masked;
    private bool $debug;

    /**
     * @param list<string> $masked
     */
    public function __construct(string $apiKey, string $projectId, string $endpointUrl, array $masked, bool $debug)
    {
        $this->apiKey = $apiKey;
        $this->projectId = $projectId;
        $this->endpointUrl = $endpointUrl;
        $this->masked = $masked;
        $this->debug = $debug;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getEndpointUrl(): string
    {
        return $this->endpointUrl;
    }

    /**
     * @return list<string>
     */
    public function getMasked(): array
    {
        return $this->masked;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }
}
