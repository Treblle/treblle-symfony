<?php

declare(strict_types=1);

namespace Treblle\Symfony\Payload;

use Throwable;
use Treblle\Symfony\Doctrine\QueryCollector;
use Treblle\Symfony\Masking\DataMasker;
use Treblle\Symfony\TreblleBundle;
use Treblle\Symfony\DependencyInjection\TreblleConfiguration;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PayloadBuilder
{
    private ?string $cachedServerIp = null;

    public function __construct(
        private readonly TreblleConfiguration $configuration,
        private readonly DataMasker $masker,
        private readonly QueryCollector $queryCollector,
    ) {}

    public function build(
        Request $request,
        Response $response,
        float $requestStartedAt,
        ?string $routePath,
        array $errors,
    ): array {
        return [
            'sdk_token' => $this->configuration->getSdkToken(),
            'api_key' => $this->configuration->getApiKey(),
            'sdk' => TreblleBundle::SDK_NAME,
            'version' => TreblleBundle::SDK_VERSION,
            'data' => [
                'server' => $this->buildServer(),
                'language' => $this->buildLanguage(),
                'request' => $this->buildRequest($request, $routePath),
                'response' => $this->buildResponse($request, $response, $requestStartedAt, $errors),
                'errors' => $errors,
                'queries' => $this->queryCollector->all(),
            ],
        ];
    }

    private function buildServer(): array
    {
        return [
            'ip' => $this->resolveServerIp(),
            'timezone' => date_default_timezone_get() ?: 'UTC',
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? null,
            'os' => [
                'name' => php_uname('s') ?: null,
                'release' => php_uname('r') ?: null,
                'architecture' => php_uname('m') ?: null,
            ],
        ];
    }

    private function resolveServerIp(): string
    {
        if ($this->cachedServerIp !== null) {
            return $this->cachedServerIp;
        }

        $ip = $_SERVER['SERVER_ADDR'] ?? '';

        if (empty($ip)) {
            try {
                $hostname = gethostname();
                if ($hostname !== false) {
                    $resolved = gethostbyname($hostname);
                    // gethostbyname returns the input unchanged when DNS lookup fails
                    $ip = ($resolved !== $hostname) ? $resolved : 'bogon';
                } else {
                    $ip = 'bogon';
                }
            } catch (Throwable) {
                $ip = 'bogon';
            }
        }

        return $this->cachedServerIp = ($ip ?: 'bogon');
    }

    private function buildLanguage(): array
    {
        return [
            'name' => 'php',
            'version' => PHP_VERSION,
        ];
    }

    private function buildRequest(Request $request, ?string $routePath): array
    {
        $body = $this->parseRequestBody($request);
        $query = $request->query->all();
        $headers = $this->normalizeHeaders($request->headers->all());

        return [
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'ip' => $request->getClientIp() ?: 'bogon',
            'url' => $request->getUri(),
            'user_agent' => $request->headers->get('User-Agent', '') ?? '',
            'method' => strtoupper($request->getMethod()),
            'headers' => $this->masker->mask($headers),
            'body' => $this->masker->mask($body),
            'route_path' => $routePath,
            'query' => $this->masker->mask($query),
        ];
    }

    private function buildResponse(
        Request $request,
        Response $response,
        float $requestStartedAt,
        array $errors,
    ): array {
        $content = $response->getContent() ?: '';
        $size = mb_strlen($content, '8bit');
        $contentType = $response->headers->get('Content-Type', '') ?? '';

        $body = [];

        if ($size > 2 * 1024 * 1024) {
            $body = ['error' => 'Payload too large', 'size' => $size];
        } elseif ($this->isJsonContentType($contentType)) {
            try {
                $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                $body = is_array($decoded) ? $decoded : [];
            } catch (Throwable) {
                $body = [];
            }
        }

        $headers = $this->normalizeHeaders($response->headers->all());

        return [
            'headers' => $this->masker->mask($headers),
            'code' => $response->getStatusCode(),
            'size' => $size,
            'load_time' => $this->calculateLoadTime($requestStartedAt),
            'body' => $this->masker->mask($body),
        ];
    }

    private function parseRequestBody(Request $request): array
    {
        $contentType = $request->headers->get('Content-Type', '') ?? '';

        if (str_contains($contentType, 'application/x-www-form-urlencoded')
            || str_contains($contentType, 'multipart/form-data')) {
            $body = $request->request->all();

            foreach ($request->files->all() as $field => $file) {
                $body[$field] = $this->buildFileMetadata($file);
            }

            $encoded = json_encode($body) ?: '{}';
            $size = mb_strlen($encoded, '8bit');

            if ($size > 2 * 1024 * 1024) {
                return ['error' => 'Payload too large', 'size' => $size];
            }

            return $body;
        }

        $content = $request->getContent();
        $size = mb_strlen($content, '8bit');

        if ($size > 2 * 1024 * 1024) {
            return ['error' => 'Payload too large', 'size' => $size];
        }

        if (empty($content)) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (Throwable) {
            return [];
        }
    }

    private function buildFileMetadata(mixed $file): mixed
    {
        if ($file instanceof UploadedFile) {
            return [
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        if (is_array($file)) {
            return array_map(fn (mixed $f) => $this->buildFileMetadata($f), $file);
        }

        return null;
    }

    private function isJsonContentType(string $contentType): bool
    {
        $normalized = strtolower($contentType);

        return str_contains($normalized, 'application/json')
            || str_contains($normalized, '+json');
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $values) {
            $normalized[$name] = is_array($values) ? implode(', ', $values) : (string) $values;
        }

        return $normalized;
    }

    private function calculateLoadTime(float $requestStartedAt): float
    {
        return round((microtime(true) - $requestStartedAt) * 1000, 2);
    }
}
