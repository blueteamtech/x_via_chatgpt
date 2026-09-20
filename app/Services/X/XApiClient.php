<?php

namespace App\Services\X;

use App\Exceptions\XApiException;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class XApiClient
{
    public function __construct(
        protected XOAuthService $oauth,
        protected ?User $user = null,
    ) {}

    public function forUser(User $user): static
    {
        $clone = clone $this;
        $clone->user = $user;

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $path, array $body = []): array
    {
        return $this->send('post', $path, $body);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function put(string $path, array $body = []): array
    {
        return $this->send('put', $path, $body);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function delete(string $path, array $body = []): array
    {
        return $this->send('delete', $path, $body);
    }

    /**
     * Upload file contents to X as multipart form data.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function upload(string $path, string $contents, string $filename, array $body = []): array
    {
        $path = ltrim($path, '/');

        $send = fn (): Response => $this->http('post')
            ->attach('media', $contents, $filename)
            ->post($path, $body);

        $response = $send();

        if ($response->status() === 401 && $this->refreshAccessToken()) {
            $response = $send();
        }

        return $this->decode($response);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function send(string $method, string $path, array $payload = []): array
    {
        $request = $this->http($method);
        $path = ltrim($path, '/');

        $response = $this->call($request, $method, $path, $payload);

        if ($response->status() === 401 && $this->refreshAccessToken()) {
            $response = $this->call($this->http($method), $method, $path, $payload);
        }

        return $this->decode($response);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function call(PendingRequest $request, string $method, string $path, array $payload): Response
    {
        return match ($method) {
            'get' => $request->get($path, $payload),
            'post' => $this->sendJsonBody($request, 'post', $path, $payload),
            'put' => $this->sendJsonBody($request, 'put', $path, $payload),
            'delete' => $payload === [] ? $request->delete($path) : $request->withQueryParameters($payload)->delete($path),
            default => throw new XApiException("Unsupported HTTP method [{$method}]."),
        };
    }

    /**
     * X rejects a JSON body that isn't an object. PHP encodes empty arrays as
     * '[]', so we force an empty payload to serialize as '{}' before sending.
     *
     * @param  array<string, mixed>  $payload
     */
    private function sendJsonBody(PendingRequest $request, string $method, string $path, array $payload): Response
    {
        if ($payload === []) {
            return $request->withBody('{}', 'application/json')->{$method}($path);
        }

        return $request->{$method}($path, $payload);
    }

    /**
     * Build an authenticated request for the X API.
     *
     * Only reads are replayed on a dropped connection. Replaying a write could
     * publish a post or send a DM twice, and X never retries usefully on a 4xx,
     * so HTTP failures are passed straight back to the caller.
     */
    protected function http(string $method = 'get'): PendingRequest
    {
        $user = $this->user();
        $this->refreshIfExpiring($user);

        $request = Http::baseUrl(config('x.api_base'))
            ->withToken((string) $user->x_access_token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(30);

        if ($method === 'get') {
            $request->retry(2, 250, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && $exception->response->serverError()), throw: false);
        }

        return $request;
    }

    protected function refreshIfExpiring(User $user): void
    {
        if ($user->x_token_expires_at === null || $user->x_token_expires_at->subMinute()->isFuture()) {
            return;
        }

        $this->refreshAccessToken();
    }

    protected function refreshAccessToken(): bool
    {
        $user = $this->user();

        if (blank($user->x_refresh_token)) {
            return false;
        }

        $staleToken = (string) $user->x_access_token;

        try {
            return Cache::lock('x-token-refresh:'.$user->getKey(), 15)
                ->block(10, fn (): bool => $this->exchangeRefreshToken($user, $staleToken));
        } catch (LockTimeoutException) {
            Log::warning('X token refresh timed out waiting for the lock', ['user_id' => $user->getKey()]);

            return false;
        }
    }

    /**
     * Trade the refresh token for a fresh access token.
     *
     * X invalidates a refresh token the moment it is used, so concurrent tool
     * calls must not both redeem it — the loser would revoke the winner's brand
     * new token and sign the user out. This runs behind a lock, and whoever
     * arrives second finds the access token already rotated and adopts it.
     */
    protected function exchangeRefreshToken(User $user, string $staleToken): bool
    {
        $user->refresh();

        if ((string) $user->x_access_token !== $staleToken) {
            $this->user = $user;

            return true;
        }

        try {
            $token = $this->oauth->refresh((string) $user->x_refresh_token);
        } catch (Throwable $exception) {
            Log::warning('X refresh token failed', ['user_id' => $user->getKey(), 'error' => $exception->getMessage()]);

            return false;
        }

        $user->forceFill([
            'x_access_token' => $token['access_token'],
            'x_refresh_token' => $token['refresh_token'] ?? $user->x_refresh_token,
            'x_token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 7200)),
        ])->save();

        $this->user = $user->refresh();

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(Response $response): array
    {
        $json = $response->json();
        $payload = is_array($json) ? $json : ['raw' => $response->body()];

        if ($response->failed()) {
            $message = $this->errorMessage($payload, $response->status());

            throw new XApiException($message, $response->status(), $payload);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function errorMessage(array $payload, int $status): string
    {
        if (isset($payload['detail']) && is_string($payload['detail'])) {
            return $payload['detail'];
        }

        if (isset($payload['title']) && is_string($payload['title'])) {
            return $payload['title'];
        }

        $errors = $payload['errors'] ?? null;

        if (is_array($errors) && isset($errors[0]['message'])) {
            return (string) $errors[0]['message'];
        }

        return "X API request failed with HTTP {$status}.";
    }

    protected function user(): User
    {
        if (! $this->user instanceof User || blank($this->user->x_access_token)) {
            throw new XApiException('Connect an X account before using this tool.');
        }

        return $this->user;
    }
}
