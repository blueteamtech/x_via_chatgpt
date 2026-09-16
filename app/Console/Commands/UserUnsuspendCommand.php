<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserUnsuspendCommand extends Command
{
    protected $signature = 'user:unsuspend {id : User ID to unsuspend}';

    protected $description = 'Restore a suspended user — sets subscription_status to active.';

    public function handle(): int
    {
        $user = User::find($this->argument('id'));

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $user->forceFill([
            'subscription_status' => 'active',
        ])->save();

        $this->info("Unsuspended user #{$user->id} (@{$user->username}). Subscription status: active.");

        return self::SUCCESS;
    }
}
