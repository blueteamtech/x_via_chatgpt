<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client as BaseClient;
use Laravel\Passport\Scope;

class PassportClient extends BaseClient
{
    /**
     * Hosts whose clients may skip our consent screen.
     *
     * @var list<string>
     */
    public const TRUSTED_REDIRECT_HOSTS = ['chatgpt.com', 'chat.openai.com'];

    /**
     * Skip our consent screen for ChatGPT.
     *
     * The user already approved the real permissions on X's authorize screen, so a
     * second prompt adds nothing. Client registration is open because ChatGPT
     * requires it, so every redirect URI must point at a trusted host before we
     * issue a code without asking.
     *
     * @param  Scope[]  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        $redirectUris = $this->redirect_uris ?? [];

        if ($redirectUris === []) {
            return false;
        }

        foreach ($redirectUris as $uri) {
            if (! $this->isTrustedRedirectUri((string) $uri)) {
                return false;
            }
        }

        return true;
    }

    protected function isTrustedRedirectUri(string $uri): bool
    {
        $parts = parse_url($uri);

        return ($parts['scheme'] ?? '') === 'https'
            && in_array(strtolower($parts['host'] ?? ''), self::TRUSTED_REDIRECT_HOSTS, true);
    }
}
