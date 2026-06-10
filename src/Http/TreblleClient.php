<?php

declare(strict_types=1);

namespace Treblle\Symfony\Http;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;

final class TreblleClient implements TreblleClientInterface
{
    public function __construct(
        private readonly TreblleConfiguration $configuration,
        private readonly CircuitBreaker $circuitBreaker,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function send(array $payload): void
    {
        if (! $this->circuitBreaker->isAllowed()) {
            $this->logger->debug('Circuit open — skipping payload dispatch.');

            return;
        }

        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $compressed = gzencode($json, 1);

            if ($compressed === false) {
                $this->logger->warning('Failed to GZIP-compress payload.');

                return;
            }

            $ch = curl_init($this->configuration->getIngressUrl());

            if ($ch === false) {
                $this->logger->warning('Failed to initialize cURL handle.');

                return;
            }

            $retryAfter = null;

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $compressed,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Encoding: gzip',
                    'x-api-key: ' . $this->configuration->getSdkToken(),
                ],
                CURLOPT_WRITEFUNCTION => static fn ($ch, string $data): int => strlen($data),
                CURLOPT_HEADERFUNCTION => static function ($ch, string $header) use (&$retryAfter): int {
                    if (stripos($header, 'Retry-After:') === 0) {
                        $value = trim(substr($header, strlen('Retry-After:')));
                        // Retry-After is either a delay-seconds integer or an HTTP-date
                        $retryAfter = is_numeric($value)
                            ? (int) $value
                            : (int) max(0, strtotime($value) - time());
                    }

                    return strlen($header);
                },
            ]);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);

            if ($error !== '') {
                $this->logger->warning('cURL error while sending payload.', ['error' => $error]);
                $this->circuitBreaker->onFailure(500);

                return;
            }

            $this->logger->debug('Payload sent.', ['http_code' => $httpCode]);

            if ($httpCode === 429 || $httpCode >= 500) {
                $this->logger->warning('Treblle ingress returned an error response.', ['http_code' => $httpCode]);
                $this->circuitBreaker->onFailure($httpCode, $retryAfter);

                return;
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                $this->circuitBreaker->onSuccess();
            }
        } catch (Throwable $e) {
            $this->logger->warning('Exception while sending payload.', ['exception' => $e->getMessage()]);
        }
    }

}
