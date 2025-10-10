<?php

declare(strict_types=1);

namespace Treblle\Symfony\Exceptions;

use Exception;

final class TreblleException extends Exception
{
    public static function missingApiKey(): self
    {
        return new TreblleException(
            message: 'No Api Key configured for Treblle. Ensure this is set in your .env before trying again.',
        );
    }

    public static function missingSdkToken(): self
    {
        return new TreblleException(
            message: 'No SDK Token configured for Treblle. Ensure this is set in your .env before trying again.',
        );
    }
}
