<?php

namespace Treblle\Symfony\DataTransferObjects;

use JsonSerializable;
use Treblle\Php\DataTransferObject\Data;

/**
 * Holds data needed for Treblle data transmission via Symfony's Messenger component
 * This DTO is injected to TransmitTreblleData instance
 */
readonly class TrebllePayloadData
{
    public function __construct(
        private string $apiKey,
        private string $sdkToken,
        private bool $debug,
        private string $sdkName,
        private float $sdkVersion,
        private ?string $url,
        private Data $data,
    ){
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getSdkToken(): string
    {
        return $this->sdkToken;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Returns data for JSON serialization before message is dispatched
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'api_key' => $this->apiKey,
            'sdk_token' => $this->sdkToken,
            'sdk_name' => $this->sdkName,
            'sk_version' => $this->sdkVersion,
            'url' => $this->url,
            'debug' => $this->debug,
            'data' => $this->data,
        ];
    }
}
