<?php

declare(strict_types=1);

namespace Treblle\Symfony\Doctrine;

final class QueryCollector
{
    private const MAX_QUERIES = 100;

    private array $queries = [];

    public function add(string $sql, float $time): void
    {
        if (count($this->queries) < self::MAX_QUERIES) {
            $this->queries[] = ['sql' => $sql, 'time' => $time];
        }
    }

    public function all(): array
    {
        return $this->queries;
    }

    public function reset(): void
    {
        $this->queries = [];
    }
}
