<?php

declare(strict_types=1);

namespace Treblle\Symfony;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * TreblleBundle is the main Symfony Bundle for the Treblle SDK integration.
 *
 * This bundle provides API monitoring, observability, and analytics capabilities
 * for Symfony applications by integrating with the Treblle platform.
 *
 * @see https://treblle.com
 * @see https://docs.treblle.com/en/integrations/symfony
 */
final class TreblleBundle extends Bundle
{
    /**
     * The name identifier of this SDK.
     */
    public const SDK_NAME = 'symfony';

    /**
     * The current version of this SDK.
     */
    public const SDK_VERSION = 3.0;
}
