<?php

declare(strict_types=1);

namespace Treblle\Symfony\Doctrine;

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
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

    public function query(string $sql): Result
    {
        $start = microtime(true);

        try {
            return parent::query($sql);
        } finally {
            $this->collector->add($sql, round((microtime(true) - $start) * 1000, 2));
        }
    }

    public function exec(string $sql): int|string
    {
        $start = microtime(true);

        try {
            return parent::exec($sql);
        } finally {
            $this->collector->add($sql, round((microtime(true) - $start) * 1000, 2));
        }
    }
}
