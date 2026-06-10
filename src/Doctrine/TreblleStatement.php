<?php

declare(strict_types=1);

namespace Treblle\Symfony\Doctrine;

use Doctrine\DBAL\Driver\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

final class TreblleStatement extends AbstractStatementMiddleware
{
    public function __construct(
        Statement $statement,
        private readonly string $sql,
        private readonly QueryCollector $collector,
    ) {
        parent::__construct($statement);
    }

    public function execute($params = null): Result
    {
        $start = microtime(true);

        try {
            return parent::execute($params);
        } finally {
            $this->collector->add($this->sql, round((microtime(true) - $start) * 1000, 2));
        }
    }
}
