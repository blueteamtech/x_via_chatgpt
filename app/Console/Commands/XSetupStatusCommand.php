<?php

namespace App\Console\Commands;

use App\Services\X\XOAuthService;
use Illuminate\Console\Command;

class XSetupStatusCommand extends Command
{
    protected $signature = 'x:status';

    protected $description = 'Show X Developer Portal, ChatGPT, and Laravel Cloud setup status';

    public function handle(XOAuthService $oauth): int
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $callback = $oauth->isConfigured() || filled(config('app.url'))
            ? $oauth->redirectUri()
            : $appUrl.'/auth/x/callback';

        $this->info('X via ChatGPT — setup status');
        $this->newLine();

        $this->line('Laravel');
        $this->row('APP_URL', $appUrl !== '' ? $appUrl : 'missing');
        $this->row('APP_KEY', filled(config('app.key')) ? 'set' : 'missing — php artisan key:generate');
        $this->row('HTTPS ready for Cloud', str_starts_with($appUrl, 'https://') ? 'yes' : 'set APP_URL to your https://*.laravel.cloud URL');
        $this->newLine();

        $this->line('X Developer Portal (browser)');
        $this->row('X_CLIENT_ID', filled(config('x.client_id')) ? 'set' : 'missing');
        $this->row('X_CLIENT_SECRET', filled(config('x.client_secret')) ? 'set' : 'missing');
        $this->row('Callback URL to paste in X', $callback);
        $this->row('Website URL to paste in X', $appUrl !== '' ? $appUrl : '(set APP_URL first)');
        $this->newLine();

        $this->line('ChatGPT custom connector (browser, after Cloud deploy)');
        $this->row('Connector URL', $appUrl !== '' ? $appUrl.'/mcp' : '(set APP_URL first)');
        $this->row('Authentication', 'OAuth');
        $this->newLine();

        $this->line('Copy from X Developer Portal → your app → Keys and tokens / User authentication:');
        $this->line('  1. OAuth 2.0 Client ID');
        $this->line('  2. OAuth 2.0 Client Secret');
        $this->line('  3. App permissions: Read and write + Direct Messages if you want DMs');
        $this->line('  4. Type of App: Web App (confidential client)');
        $this->line('  5. Callback URI / Redirect URL: exactly the callback above');
        $this->newLine();
        $this->line('Then set env vars locally in .env, and on Laravel Cloud with:');
        $this->line('  cloud environment:variables --action=set --key=X_CLIENT_ID --value=...');
        $this->line('  cloud environment:variables --action=set --key=X_CLIENT_SECRET --value=...');
        $this->line('  cloud environment:variables --action=set --key=APP_URL --value=https://YOUR_APP.laravel.cloud');
        $this->line('  cloud environment:variables --action=set --key=X_REDIRECT_URI --value='.$callback);

        return self::SUCCESS;
    }

    protected function row(string $label, string $value): void
    {
        $this->line(sprintf('  %-32s %s', $label, $value));
    }
}
