<?php

declare(strict_types=1);

namespace Treblle\Symfony\Messenger;

final class SendTrebllePayload
{
    public function __construct(
        public readonly array $payload,
    ) {
    }
}
