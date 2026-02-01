<?php

declare(strict_types=1);

namespace Treblle\Symfony\DependencyInjection;

/**
 * TreblleConfiguration is an immutable value object that holds all Treblle configuration values.
 *
 * This class stores and provides access to all configuration settings needed to
 * integrate the Treblle SDK with a Symfony application, including API credentials,
 * environment settings, security options, and custom endpoint URLs.
 *
 * @see Configuration For the configuration tree definition
 */
final readonly class TreblleConfiguration
{
    /**
     * Creates a new TreblleConfiguration instance.
     *
     * @param string $apiKey The Treblle API key (project ID)
     * @param string $sdkToken The Treblle SDK token (API key)
     * @param string|null $url Optional custom Treblle endpoint URL
     * @param string $ignoredEnvironments Comma-separated list of environments to ignore
     * @param array<int,string> $maskedFields List of field names to mask in requests/responses
     * @param array<int,string> $excludedHeaders List of header patterns to exclude from tracking
     * @param bool $debug Whether to enable debug mode
     */
    public function __construct(
        private string  $apiKey,
        private string  $sdkToken,
        private ?string $url = null,
        private string  $ignoredEnvironments = 'dev,test,testing',
        private array   $maskedFields = [],
        private array   $excludedHeaders = [],
        private bool    $debug = false,
        private bool $queueEnabled = false,
    ) {
    }

    /**
     * Returns the Treblle API key (project ID).
     *
     * @return string The API key
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Returns the Treblle SDK token (API key).
     *
     * @return string The SDK token
     */
    public function getSdkToken(): string
    {
        return $this->sdkToken;
    }

    /**
     * Returns the comma-separated list of environments to ignore.
     *
     * @return string Comma-separated environment names (e.g., "dev,test,testing")
     */
    public function getIgnoredEnvironments(): string
    {
        return $this->ignoredEnvironments;
    }

    /**
     * Returns the list of field names to mask in requests and responses.
     *
     * @return array<int,string> Array of field names that should be masked
     */
    public function getMaskedFields(): array
    {
        return $this->maskedFields;
    }

    /**
     * Returns whether debug mode is enabled.
     *
     * @return bool True if debug mode is enabled, false otherwise
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Returns the custom Treblle endpoint URL, if configured.
     *
     * @return string|null The custom URL or null if using default endpoint
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Returns the list of header patterns to exclude from tracking.
     *
     * @return array<int,string> Array of header patterns to exclude
     */
    public function getExcludedHeaders(): array
    {
        return $this->excludedHeaders;
    }

    /**
     * Returns whether queue transmission is enabled.
     *
     * @return bool True if debug mode is enabled, false otherwise
     */
    public function isQueueEnabled(): bool
    {
        return $this->queueEnabled;
    }
}
