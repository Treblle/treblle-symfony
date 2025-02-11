<?php

declare(strict_types=1);

namespace Treblle\Symfony\Helpers;

final class Normalise
{
    /**
     * @param array<string, array<string>> $allHeaders
     * @return array<string, string>
     */
    public static function headers(array $allHeaders): array
    {
        $headers = [];
        foreach ($allHeaders as $name => $value) {
            $headers[$name] = implode(', ', $value);
        }

        return $headers;
    }
}
