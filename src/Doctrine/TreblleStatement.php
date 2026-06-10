<?php

declare(strict_types=1);

namespace Treblle\Symfony\Doctrine;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
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

    public function execute(): Result
    {
        $start = microtime(true);

        try {
            return parent::execute();
        } finally {
            $this->collector->add($this->sql, round((microtime(true) - $start) * 1000, 2));
        }
    }
}
