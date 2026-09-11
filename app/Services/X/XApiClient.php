<?php

namespace App\Services\X;

use App\Exceptions\XApiException;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function send(string $method, string $path, array $payload = []): array
    {
        $request = $this->http();
        $path = ltrim($path, '/');

        $response = $this->call($request, $method, $path, $payload);

        if ($response->status() === 401 && $this->refreshAccessToken()) {
            $response = $this->call($this->http(), $method, $path, $payload);
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
            'post' => $request->post($path, $payload),
            'put' => $request->put($path, $payload),
            'delete' => $payload === [] ? $request->delete($path) : $request->withQueryParameters($payload)->delete($path),
            default => throw new XApiException("Unsupported HTTP method [{$method}]."),
        };
    }

    protected function http(): PendingRequest
    {
        $user = $this->user();
        $this->refreshIfExpiring($user);

        return Http::baseUrl(config('x.api_base'))
            ->withToken((string) $user->x_access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(1, 250, throw: false);
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

        try {
            $token = $this->oauth->refresh((string) $user->x_refresh_token);
        } catch (\Throwable $exception) {
            Log::warning('X refresh token failed', ['user_id' => $user->id, 'error' => $exception->getMessage()]);

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
