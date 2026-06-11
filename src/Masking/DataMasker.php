<?php

declare(strict_types=1);

namespace Treblle\Symfony\Masking;

final class DataMasker
{
    private array $maskedKeys;

    public function __construct(array $maskedKeys = [])
    {
        $this->maskedKeys = array_map('strtolower', $maskedKeys);
    }

    private const MAX_DEPTH = 10;

    public function mask(mixed $data): mixed
    {
        if (is_array($data)) {
            return $this->maskArray($data, 0);
        }

        return $data;
    }

    private function maskArray(array $data, int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return [];
        }

        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->shouldMask($key)) {
                $result[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $result[$key] = $this->maskArray($value, $depth + 1);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function shouldMask(string $key): bool
    {
        return in_array(strtolower($key), $this->maskedKeys, true);
    }

    private function maskValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_string($value)) {
            return str_repeat('*', strlen($value));
        }

        if (is_int($value) || is_float($value)) {
            return str_repeat('*', strlen((string) $value));
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item) => $this->maskValue($item), $value);
        }

        return $value;
    }
}
