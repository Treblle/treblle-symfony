<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

class TreblleConfiguration
{
    /** @var string $apiKey */
    private string $apiKey;

    /** @var string $projectId */
    private string $projectId;

    /** @var string $endpointUrl */
    private string $endpointUrl;

    /** @var list<string> $masked */
    private array $masked;

    /** @var list<string> $ignore */
    private array $ignore;

    /** @var bool $debug */
    private bool $debug;

    /**
     * @param string $apiKey
     * @param string $projectId
     * @param string $endpointUrl
     * @param array<int,string> $masked
     * @param bool $debug
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

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }

    /**
     * @return string
     */
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

    /**
     * @return bool
     */
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
