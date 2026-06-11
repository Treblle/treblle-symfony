<?php

declare(strict_types=1);

namespace Treblle\Symfony\Messenger;

use Treblle\Symfony\Http\TreblleClient;

final class SendTrebllePayloadHandler
{
    public function __construct(
        private readonly TreblleClient $client,
    ) {}

    public function __invoke(SendTrebllePayload $message): void
    {
        $this->client->send($message->payload);
    }
}
