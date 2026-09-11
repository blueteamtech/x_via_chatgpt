<?php

namespace App\Providers;

use App\Models\PassportClient;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Passport::useClientModel(PassportClient::class);
    }

    public function boot(): void
    {
        Passport::authorizationView(fn (array $parameters) => view('mcp.authorize', $parameters));
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
