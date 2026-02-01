<?php

namespace Treblle\Symfony\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;
use Treblle\Symfony\DataTransferObjects\TrebllePayloadData;

/**
 * Message instance which hold payload data for transmitting via Symfony's Messenger component
 */
#[AsMessage('treblle')]
final class TransmitTreblleData
{
    public function __construct(
        private TrebllePayloadData $payloadData
    ){
    }

    public function getPayloadData(): TrebllePayloadData
    {
        return $this->payloadData;
    }
}
