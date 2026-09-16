<?php

namespace App\Services\Credits;

use App\Exceptions\CircuitBreakerTrippedException;
use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    private const CACHE_PREFIX = 'circuit:credits:daily:';

    /**
     * Check and increment global daily spend. Throws if today's total
     * exceeds the configured daily credit cap.
     *
     * @throws CircuitBreakerTrippedException
     */
    public function guard(int $credits): void
    {
        if ($credits <= 0) {
            return;
        }

        $cap = (int) config('credits.global_daily_max_credits', 500);
        $key = self::CACHE_PREFIX.now()->format('Y-m-d');

        // Ensure a base value with correct TTL exists before increment.
        Cache::add($key, 0, now()->endOfDay());

        $newTotal = Cache::increment($key, $credits);

        if ($newTotal > $cap) {
            // Reverse the increment so the accounting doesn't over-attribute.
            Cache::decrement($key, $credits);

            throw new CircuitBreakerTrippedException($newTotal - $credits, $cap);
        }
    }

    /**
     * Current total credits deducted today across ALL users.
     */
    public function todaySpend(): int
    {
        return (int) Cache::get(self::CACHE_PREFIX.now()->format('Y-m-d'), 0);
    }
}
