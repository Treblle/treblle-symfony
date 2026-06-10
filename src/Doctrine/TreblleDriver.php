<?php

declare(strict_types=1);

namespace Treblle\Symfony\Doctrine;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Connection as DriverConnection;

final class TreblleDriver extends AbstractDriverMiddleware
{
    public function __construct(
        DriverInterface $driver,
        private readonly QueryCollector $collector,
    ) {
        parent::__construct($driver);
    }

    public function connect(array $params): DriverConnection
    {
        return new TreblleConnection(parent::connect($params), $this->collector);
    }
}
