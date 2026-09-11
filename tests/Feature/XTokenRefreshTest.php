<?php

use App\Models\User;
use App\Services\X\XApiClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['x.client_id' => 'test-client', 'x.client_secret' => 'test-secret']);
});

function fakeXWithOneUsableRefresh(): void
{
    Http::fake([
        'api.x.com/2/oauth2/token' => Http::sequence()
            ->push(['access_token' => 'fresh-token', 'refresh_token' => 'refresh-2', 'expires_in' => 7200])
            // A second redemption would fail for real: X kills a refresh token on use.
            ->pushStatus(400),
        'api.x.com/*' => Http::response(['data' => ['id' => '1']]),
    ]);
}

function tokenRequestCount(): int
{
    return collect(Http::recorded())
        ->filter(fn (array $pair) => str_contains($pair[0]->url(), 'oauth2/token'))
        ->count();
}

it('renews an expired token before calling X', function () {
    fakeXWithOneUsableRefresh();

    $user = connectedUser([
        'x_access_token' => 'stale-token',
        'x_refresh_token' => 'refresh-1',
        'x_token_expires_at' => now()->subMinute(),
    ]);

    app(XApiClient::class)->forUser($user)->get('/users/me');

    expect($user->fresh()->x_access_token)->toBe('fresh-token');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/users/me')
        && $request->hasHeader('Authorization', 'Bearer fresh-token'));
});

it('lets a second request reuse a token another request just renewed', function () {
    fakeXWithOneUsableRefresh();

    $user = connectedUser([
        'x_access_token' => 'stale-token',
        'x_refresh_token' => 'refresh-1',
        'x_token_expires_at' => now()->subMinute(),
    ]);

    // Two in-flight tool calls, each holding its own copy of the stale token.
    $first = User::find($user->getKey());
    $second = User::find($user->getKey());

    app(XApiClient::class)->forUser($first)->get('/users/me');
    app(XApiClient::class)->forUser($second)->get('/users/me');

    expect(tokenRequestCount())->toBe(1);

    Http::assertSentCount(3);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/users/me')
        && $request->hasHeader('Authorization', 'Bearer stale-token'));
});
