<?php

declare(strict_types=1);

namespace Treblle\Symfony\Http;

final class CircuitBreaker
{
    private const KEY_STATE = 'treblle_cb_state';
    private const KEY_OPEN_UNTIL = 'treblle_cb_open_until';
    private const KEY_FAILURES = 'treblle_cb_failures';

    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private const FAILURE_THRESHOLD = 5;
    private const DEFAULT_BACKOFF_SECONDS = 60;
    private const MAX_BACKOFF_SECONDS = 300;

    public function isAllowed(): bool
    {
        if (! $this->isAvailable()) {
            return true;
        }

        $state = apcu_fetch(self::KEY_STATE);

        if ($state === false || $state === self::STATE_CLOSED) {
            return true;
        }

        if ($state === self::STATE_OPEN) {
            $openUntil = apcu_fetch(self::KEY_OPEN_UNTIL);

            if ($openUntil !== false && time() < (int) $openUntil) {
                return false;
            }

            apcu_store(self::KEY_STATE, self::STATE_HALF_OPEN);

            return true;
        }

        // STATE_HALF_OPEN — probe already dispatched by another worker
        return false;
    }

    public function onSuccess(): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        apcu_store(self::KEY_STATE, self::STATE_CLOSED);
        apcu_store(self::KEY_FAILURES, 0);
    }

    public function onFailure(int $statusCode, ?int $retryAfterSeconds = null): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        if ($statusCode === 429) {
            $backoff = $retryAfterSeconds ?? self::DEFAULT_BACKOFF_SECONDS;
            apcu_store(self::KEY_OPEN_UNTIL, time() + $backoff);
            apcu_store(self::KEY_STATE, self::STATE_OPEN);
            apcu_store(self::KEY_FAILURES, 0);

            return;
        }

        if ($statusCode >= 500) {
            $failures = apcu_inc(self::KEY_FAILURES, 1, $success);

            if (! $success) {
                apcu_store(self::KEY_FAILURES, 1);
                $failures = 1;
            }

            $currentState = apcu_fetch(self::KEY_STATE);

            if ((int) $failures >= self::FAILURE_THRESHOLD || $currentState === self::STATE_HALF_OPEN) {
                $exponent = max(0, (int) $failures - self::FAILURE_THRESHOLD);
                $backoff = (int) min(self::MAX_BACKOFF_SECONDS, self::DEFAULT_BACKOFF_SECONDS * (2 ** $exponent));
                apcu_store(self::KEY_OPEN_UNTIL, time() + $backoff);
                apcu_store(self::KEY_STATE, self::STATE_OPEN);
            }
        }
    }

    private function isAvailable(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }
}
