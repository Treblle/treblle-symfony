<?php

declare(strict_types=1);

namespace Treblle\Symfony\Metadata;

final class MetadataRegistry
{
    private array $metadata = [];

    public function add(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }

    public function all(): array
    {
        return $this->metadata;
    }

    public function reset(): void
    {
        $this->metadata = [];
    }
}
