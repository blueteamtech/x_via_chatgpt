<?php

namespace App\Services\Credits;

use App\Exceptions\CreditsExhaustedException;
use App\Models\User;

class CreditManager
{
    /**
     * Charge credits to a user for a completed action.
     *
     * Grandfathered users (null tier) are tracked but not capped.
     * Real tier users are capped at their monthly allowance.
     *
     * @throws CreditsExhaustedException
     */
    public function charge(User $user, int $credits): void
    {
        if ($credits <= 0) {
            return;
        }

        $this->rolloverIfNewMonth($user);

        $tier = $user->subscription_tier;
        $allowance = $tier !== null ? (config('credits.tiers.'.$tier) ?? 0) : null;
        $wouldBe = $user->credits_used_this_month + $credits;

        if ($allowance !== null && $wouldBe > $allowance) {
            throw new CreditsExhaustedException($tier, $user->credits_used_this_month, $allowance);
        }

        $user->forceFill(['credits_used_this_month' => $wouldBe])->save();
    }

    /**
     * Reset the monthly counter on the first tool call of a new calendar month.
     */
    protected function rolloverIfNewMonth(User $user): void
    {
        $reset = $user->credits_reset_at;

        if ($reset !== null && $reset->isSameMonth(now()) && $reset->isSameYear(now())) {
            return;
        }

        $user->forceFill([
            'credits_used_this_month' => 0,
            'credits_reset_at' => now()->startOfMonth(),
        ])->save();
    }

    /**
     * Snapshot of the user's current credit state (for x-me tool).
     *
     * @return array{tier: ?string, used: int, remaining: ?int, allowance: ?int, resets: ?string}
     */
    public function status(User $user): array
    {
        $this->rolloverIfNewMonth($user);

        $tier = $user->subscription_tier;
        $allowance = $tier !== null ? (config('credits.tiers.'.$tier) ?? 0) : null;

        return [
            'tier' => $tier,
            'used' => (int) $user->credits_used_this_month,
            'allowance' => $allowance,
            'remaining' => $allowance !== null ? max(0, $allowance - $user->credits_used_this_month) : null,
            'resets' => now()->startOfMonth()->addMonth()->toIso8601String(),
        ];
    }
}
