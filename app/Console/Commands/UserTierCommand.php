<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserTierCommand extends Command
{
    protected $signature = 'user:tier {id : User ID} {--tier= : publisher | pro | power | none}';

    protected $description = 'Set a user\'s subscription tier. Use --tier=none to remove.';

    public function handle(): int
    {
        $user = User::find($this->argument('id'));

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $tier = $this->option('tier');
        $validTiers = array_keys(config('credits.tiers', []));

        if ($tier !== 'none' && ! in_array($tier, $validTiers, true)) {
            $this->error('Tier must be one of: '.implode(', ', $validTiers).', none.');

            return self::FAILURE;
        }

        $user->forceFill([
            'subscription_tier' => $tier === 'none' ? null : $tier,
            'subscription_status' => $tier === 'none' ? null : 'active',
            'subscription_started_at' => $user->subscription_started_at ?? now(),
        ])->save();

        $this->info("Set user #{$user->id} (@{$user->username}) tier to {$tier}.");

        return self::SUCCESS;
    }
}
