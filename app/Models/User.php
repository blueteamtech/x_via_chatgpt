<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'x_id',
    'x_access_token',
    'x_refresh_token',
    'x_token_expires_at',
    'x_token_scopes',
])]
#[Hidden(['password', 'remember_token', 'x_access_token', 'x_refresh_token'])]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'x_access_token' => 'encrypted',
            'x_refresh_token' => 'encrypted',
            'x_token_expires_at' => 'datetime',
            'x_token_scopes' => 'array',
            'subscription_started_at' => 'datetime',
            'credits_reset_at' => 'datetime',
            'is_beta' => 'boolean',
        ];
    }
}
