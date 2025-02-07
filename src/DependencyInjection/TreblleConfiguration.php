<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

final class TreblleConfiguration
{
    private string $apiKey;

    private string $projectId;

    private string $endpointUrl;

    /** @var list<string> */
    private array $masked;

    /** @var list<string> */
    private array $ignore;

    private bool $debug;

    /**
     * @param array<int,string> $masked
     * @param array<int,string> $ignore
     */
    public function __construct(string $apiKey, string $projectId, string $endpointUrl, array $masked, bool $debug, array $ignore = [])
    {
        $this->apiKey = $apiKey;
        $this->projectId = $projectId;
        $this->endpointUrl = $endpointUrl;
        $this->masked = $masked;
        $this->ignore = $ignore;
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
     * @return array<int,string>
     */
    public function getMasked(): array
    {
        return $this->masked;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @return array<int,string>
     */
    public function getIgnored(): array
    {
        return $this->ignore;
    }
}
