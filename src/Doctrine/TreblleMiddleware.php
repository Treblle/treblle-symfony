<?php

declare(strict_types=1);

namespace Treblle\Symfony\Doctrine;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

final class TreblleMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly QueryCollector $collector)
    {
    }

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new TreblleDriver($driver, $this->collector);
    }
}
