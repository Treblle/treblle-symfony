<?php

namespace Treblle\Symfony\MessageHandler;

use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use Treblle\Symfony\DataTransferObjects\TrebllePayloadData;
use Treblle\Symfony\Message\TransmitTreblleData;

/**
 * MessageHandler instance
 *
 * Responsible for sending Treblle data via Queued transmission
 * Note: Real transmission flow will depend on Messenger configuration
 */
#[AsMessageHandler(fromTransport: 'treblle')]
final class TransmitTreblleDataHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private HttpClientInterface $client,
    ){
    }

    public function __invoke(TransmitTreblleData $message): void
    {
        $payload = $message->getPayloadData();

        try {
            $jsonPayload = json_encode($payload->toArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logger->error(sprintf('[TREBLLE] Failed to create json payload for queue transmission. Error: %s', $e->getMessage()));

            return;
        }

        $compressedPayload = gzencode($jsonPayload);

        try {

            $url = $this->getBaseUrl($payload);

            // Log the payload being sent (only in debug mode)
            if ($payload->isDebug()) {
                $this->logger->info('Treblle: Sending payload', [
                    'url' => $url,
                    'api_key' => $payload->getApiKey(),
                    'sdk_token' => mb_substr($payload->getSdkToken(), 0, 10) . '...',
                    'payload_size' => mb_strlen($jsonPayload),
                    'compressed_size' => mb_strlen($compressedPayload),
                    'payload' => json_decode($jsonPayload, true), // Log as array for better readability
                ]);
            }

            // Use Symfony's HTTP client for better integration and testing
            $response = $this->client->withOptions(
                (new HttpOptions())
                    ->setHeaders([
                        'Content-Type' => 'application/json',
                        'Content-Encoding' => 'gzip',
                        'x-api-key' => $payload->getSdkToken(),
                        'Accept-Encoding' => 'gzip',
                    ])->setBody($compressedPayload)
                    ->setTimeout(3)
                    ->setMaxDuration(3)
                    ->verifyPeer(false)
                    ->toArray()
            )->request(method: 'POST', url: $url);

            // Log the response (only in debug mode)
            if ($payload->isDebug()) {
                $this->logger->info('Treblle: Response received', [
                    'status_code' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                    'body' => $response->getContent(),
                ]);
            }

        } catch (Throwable $e) {
            $this->logger->error('Treblle: Failed to send data', [
                'error' => $e->getMessage(),
                'trace' => $payload->isDebug() ? $e->getTraceAsString() : null,
            ]);

            if ($payload->isDebug()) {
                throw $e;
            }
        }

    }

    /**
     * Get the base URL for Treblle API.
     *
     * If a custom URL is provided, it will be used. Otherwise, a random
     * endpoint from the available Treblle servers is selected for load
     * balancing.
     *
     * @return string The Treblle API endpoint URL
     */
    private function getBaseUrl(TrebllePayloadData $payload): string
    {
        $urls = [
            'https://rocknrolla.treblle.com',
            'https://punisher.treblle.com',
            'https://sicario.treblle.com',
        ];

        return $payload->getUrl() ?? $urls[array_rand($urls)];
    }
}
