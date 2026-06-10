<?php

declare(strict_types=1);

namespace Treblle\Symfony\Routing;

final class PathMatcher
{
    public function isExcluded(string $requestPath, array $excludedPaths): bool
    {
        $normalized = ltrim($requestPath, '/');

        foreach ($excludedPaths as $pattern) {
            $pattern = (string) $pattern;

            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim(substr($pattern, 0, -1), '/');

                if ($prefix === '' || str_starts_with($normalized, $prefix . '/') || $normalized === $prefix) {
                    return true;
                }
            } elseif ($normalized === ltrim($pattern, '/')) {
                return true;
            }
        }

        return false;
    }
}
