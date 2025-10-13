<?php

declare(strict_types=1);

namespace Treblle\Symfony\Exceptions;

use Exception;

/**
 * TreblleException is thrown when there are configuration or runtime errors with the Treblle SDK.
 *
 * This exception is typically thrown when required credentials (API key or SDK token)
 * are missing from the configuration.
 */
final class TreblleException extends Exception
{
    /**
     * Creates an exception for missing API key configuration.
     *
     * This exception is thrown when the TREBLLE_API_KEY environment variable
     * is not set or is empty.
     *
     * @return self A new exception instance
     */
    public static function missingApiKey(): self
    {
        return new TreblleException(
            message: 'No Api Key configured for Treblle. Ensure this is set in your .env before trying again.',
        );
    }

    /**
     * Creates an exception for missing SDK token configuration.
     *
     * This exception is thrown when the TREBLLE_SDK_TOKEN environment variable
     * is not set or is empty.
     *
     * @return self A new exception instance
     */
    public static function missingSdkToken(): self
    {
        return new TreblleException(
            message: 'No SDK Token configured for Treblle. Ensure this is set in your .env before trying again.',
        );
    }
}
