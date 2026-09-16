<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserSuspendCommand extends Command
{
    protected $signature = 'user:suspend {id : User ID to suspend}';

    protected $description = 'Suspend a user immediately — blocks all tool access until unsuspended.';

    public function handle(): int
    {
        $user = User::find($this->argument('id'));

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $user->forceFill([
            'subscription_status' => 'suspended',
            'is_beta' => false,
        ])->save();

        $this->info("Suspended user #{$user->id} (@{$user->username}). All tool calls will be blocked.");

        return self::SUCCESS;
    }
}
