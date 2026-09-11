<?php

namespace App\Services\X;

use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class XOAuthService
{
    /**
     * @return array{url: string, state: string, verifier: string}
     */
    public function authorization(): array
    {
        $state = Str::random(40);
        $verifier = $this->codeVerifier();
        $challenge = $this->codeChallenge($verifier);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'scope' => implode(' ', config('x.scopes')),
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);

        return [
            'url' => config('x.authorize_url').'?'.$query,
            'state' => $state,
            'verifier' => $verifier,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token?: string, expires_in?: int, scope?: string}
     */
    public function exchangeCode(string $code, string $verifier): array
    {
        return $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
            'code_verifier' => $verifier,
        ]);
    }

    /**
     * @return array{access_token: string, refresh_token?: string, expires_in?: int, scope?: string}
     */
    public function refresh(string $refreshToken): array
    {
        return $this->tokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * @param  array{access_token: string, refresh_token?: string, expires_in?: int, scope?: string}  $token
     */
    public function upsertUser(array $token): User
    {
        $profile = Http::baseUrl(config('x.api_base'))
            ->withToken($token['access_token'])
            ->acceptJson()
            ->get('/users/me', [
                'user.fields' => 'id,name,username,profile_image_url',
            ])
            ->throw()
            ->json('data');

        if (! is_array($profile) || empty($profile['id'])) {
            throw new InvalidArgumentException('X did not return a user profile.');
        }

        $scopes = isset($token['scope'])
            ? preg_split('/\s+/', (string) $token['scope'])
            : config('x.scopes');

        $user = User::query()->firstOrNew(['x_id' => (string) $profile['id']]);

        if (! $user->exists) {
            $user->password = Str::password(32);
            $user->email = $this->placeholderEmail($profile);
        }

        $user->fill([
            'name' => (string) ($profile['name'] ?? $profile['username'] ?? 'X user'),
            'username' => (string) ($profile['username'] ?? ''),
            'x_access_token' => $token['access_token'],
            'x_refresh_token' => $token['refresh_token'] ?? null,
            'x_token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 7200)),
            'x_token_scopes' => array_values(array_filter((array) $scopes)),
        ]);
        $user->save();

        return $user;
    }

    /**
     * @param  array<string, string>  $form
     * @return array{access_token: string, refresh_token?: string, expires_in?: int, scope?: string}
     */
    protected function tokenRequest(array $form): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId(), $this->clientSecret())
            ->acceptJson()
            ->post(config('x.token_url'), $form);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            $body = $exception->response?->json();
            $detail = is_array($body)
                ? (string) ($body['error_description'] ?? $body['error'] ?? $exception->getMessage())
                : $exception->getMessage();

            throw new InvalidArgumentException('X token exchange failed: '.$detail, 0, $exception);
        }

        $json = $response->json();

        if (! is_array($json) || empty($json['access_token'])) {
            throw new InvalidArgumentException('X did not return an access token.');
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    protected function placeholderEmail(array $profile): string
    {
        return 'x-'.($profile['id'] ?? 'unknown').'@users.noreply.x.invalid';
    }

    public function redirectUri(): string
    {
        return rtrim((string) (config('x.redirect') ?: url('/auth/x/callback')), '/');
    }

    public function clientId(): string
    {
        $id = (string) config('x.client_id');

        if ($id === '') {
            throw new InvalidArgumentException('X_CLIENT_ID is not set.');
        }

        return $id;
    }

    public function clientSecret(): string
    {
        $secret = (string) config('x.client_secret');

        if ($secret === '') {
            throw new InvalidArgumentException('X_CLIENT_SECRET is not set.');
        }

        return $secret;
    }

    public function isConfigured(): bool
    {
        return filled(config('x.client_id')) && filled(config('x.client_secret'));
    }

    protected function codeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    protected function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
