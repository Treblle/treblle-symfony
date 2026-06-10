<?php

declare(strict_types=1);

namespace Treblle\Symfony\Doctrine;

use Doctrine\DBAL\Driver\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Statement;

final class TreblleConnection extends AbstractConnectionMiddleware
{
    public function __construct(
        DriverConnection $connection,
        private readonly QueryCollector $collector,
    ) {
        parent::__construct($connection);
    }

    public function prepare(string $sql): Statement
    {
        return new TreblleStatement(parent::prepare($sql), $sql, $this->collector);
    }
}
