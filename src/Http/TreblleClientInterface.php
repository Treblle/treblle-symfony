<?php

declare(strict_types=1);

namespace Treblle\Symfony\Http;

interface TreblleClientInterface
{
    public function send(array $payload): void;
}
