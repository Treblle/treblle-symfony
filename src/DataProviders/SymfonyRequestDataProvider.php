<?php

declare(strict_types=1);

namespace Treblle\Symfony\DataProviders;

use Treblle\Php\DataTransferObject\Request;
use Treblle\Php\Contract\RequestDataProvider;

final class SymfonyRequestDataProvider implements RequestDataProvider
{
    public function getRequest(): Request
    {
    }
}
