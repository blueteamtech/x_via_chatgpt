<?php

namespace App\Exceptions;

use RuntimeException;

class CircuitBreakerTrippedException extends RuntimeException
{
    public function __construct(
        public readonly int $currentSpend,
        public readonly int $cap,
    ) {
        parent::__construct(
            "Service temporarily paused — the daily spending safety cap has been reached ({$currentSpend}/{$cap} credits). Tools will resume at midnight UTC."
        );
    }
}
