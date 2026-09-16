<?php

use App\Mcp\Servers\XConnectorServer;
use App\Mcp\Tools\XCreatePostTool;
use App\Mcp\Tools\XCreateThreadTool;
use App\Mcp\Tools\XDirectMessagesTool;
use App\Mcp\Tools\XGetTopPostsTool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Cache::flush());

it('deducts credits from tier users on successful tool calls', function () {
    Http::fake(['*tweets*' => Http::response(['data' => ['id' => '123']])]);

    $user = connectedUser(['subscription_tier' => 'publisher', 'credits_used_this_month' => 0]);

    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'hello'])
        ->assertHasNoErrors();

    // Post no link = 2 credits.
    expect($user->fresh()->credits_used_this_month)->toBe(2);
});

it('does not charge credits to grandfathered users', function () {
    Http::fake(['*tweets*' => Http::response(['data' => ['id' => '123']])]);

    $user = connectedUser(['subscription_tier' => null]);

    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'hello'])
        ->assertHasNoErrors();

    // Owner/beta user is tracked but not capped. But we do track total.
    expect($user->fresh()->credits_used_this_month)->toBe(2);
});

it('blocks a tier user who has exhausted their credit budget', function () {
    Http::fake();
    $user = connectedUser(['subscription_tier' => 'publisher', 'credits_used_this_month' => 999, 'credits_reset_at' => now()->startOfMonth()]);

    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'hello'])
        ->assertSee('monthly credits');
});

it('rejects a thread with more than 25 posts', function () {
    Http::fake();
    $posts = array_fill(0, 26, 'post');

    XConnectorServer::actingAs(connectedUser())
        ->tool(XCreateThreadTool::class, ['posts' => $posts])
        ->assertHasErrors()
        ->assertSee('25-post limit');

    Http::assertNothingSent();
});

it('charges per-post credits for a thread', function () {
    Http::fake(['*tweets*' => Http::response(['data' => ['id' => 'x']])]);
    $user = connectedUser(['subscription_tier' => 'pro']);

    XConnectorServer::actingAs($user)
        ->tool(XCreateThreadTool::class, ['posts' => ['a', 'b', 'c']])
        ->assertHasNoErrors();

    // 3 posts × 2 credits = 6.
    expect($user->fresh()->credits_used_this_month)->toBe(6);
});

it('caps foreign analytics at the configured days_back limit', function () {
    Http::fake();

    XConnectorServer::actingAs(connectedUser(['x_id' => '999']))
        ->tool(XGetTopPostsTool::class, ['username' => 'naval', 'days_back' => 365])
        ->assertHasErrors()
        ->assertSee('180 days');

    Http::assertNothingSent();
});

it('blocks a 21st DM in one day', function () {
    Http::fake(['*' => Http::response(['data' => ['id' => 'x']])]);
    $user = connectedUser();

    for ($i = 1; $i <= 20; $i++) {
        XConnectorServer::actingAs($user)
            ->tool(XDirectMessagesTool::class, ['action' => 'send', 'participant_id' => (string) $i, 'text' => "msg {$i}"]);
    }

    XConnectorServer::actingAs($user)
        ->tool(XDirectMessagesTool::class, ['action' => 'send', 'participant_id' => '99', 'text' => 'msg 21'])
        ->assertHasErrors()
        ->assertSee('Daily DM limit');
});

it('blocks identical DM sent to 3+ recipients in 24h', function () {
    Http::fake(['*' => Http::response(['data' => ['id' => 'x']])]);
    $user = connectedUser();
    $identical = 'Hey, quick question about your service';

    foreach (['1', '2', '3'] as $recipient) {
        XConnectorServer::actingAs($user)
            ->tool(XDirectMessagesTool::class, ['action' => 'send', 'participant_id' => $recipient, 'text' => $identical]);
    }

    XConnectorServer::actingAs($user)
        ->tool(XDirectMessagesTool::class, ['action' => 'send', 'participant_id' => '4', 'text' => $identical])
        ->assertHasErrors()
        ->assertSee('sent to');
});

it('trips the global circuit breaker and rejects further tool calls', function () {
    config(['credits.global_daily_max_credits' => 3]);
    Http::fake(['*tweets*' => Http::response(['data' => ['id' => 'x']])]);
    $user = connectedUser(['subscription_tier' => null]);

    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'first'])
        ->assertHasNoErrors();

    // Second call would push us past the daily cap of 3.
    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'second'])
        ->assertHasErrors()
        ->assertSee('daily spending safety cap');
});
