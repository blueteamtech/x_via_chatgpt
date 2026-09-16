<?php

use App\Mcp\Servers\XConnectorServer;
use App\Mcp\Tools\XCreatePostTool;
use App\Mcp\Tools\XMeTool;
use Illuminate\Support\Facades\Http;

it('lets beta users use tools', function () {
    Http::fake(['*tweets*' => Http::response(['data' => ['id' => 'x']])]);

    $user = connectedUser(['is_beta' => true]);

    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'hi'])
        ->assertHasNoErrors();
});

it('lets active subscribers use tools', function () {
    Http::fake(['*tweets*' => Http::response(['data' => ['id' => 'x']])]);

    $user = connectedUser(['is_beta' => false, 'subscription_tier' => 'publisher', 'subscription_status' => 'active']);

    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'hi'])
        ->assertHasNoErrors();
});

it('blocks unsubscribed users from tools', function () {
    Http::fake();

    $user = connectedUser(['is_beta' => false, 'subscription_status' => null]);

    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'hi'])
        ->assertHasErrors()
        ->assertSee('subscription');

    Http::assertNothingSent();
});

it('shows a helpful message for suspended users', function () {
    Http::fake();

    $user = connectedUser(['is_beta' => false, 'subscription_status' => 'suspended']);

    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'hi'])
        ->assertHasErrors()
        ->assertSee('suspended');
});

it('shows a payment message for past_due users', function () {
    Http::fake();

    $user = connectedUser(['is_beta' => false, 'subscription_status' => 'past_due']);

    XConnectorServer::actingAs($user)
        ->tool(XCreatePostTool::class, ['text' => 'hi'])
        ->assertHasErrors()
        ->assertSee('payment');
});

it('always lets users check their status via x-me', function () {
    Http::fake(['*users/me*' => Http::response(['data' => ['id' => '1', 'username' => 'u']])]);

    $user = connectedUser(['is_beta' => false, 'subscription_status' => 'suspended']);

    XConnectorServer::actingAs($user)
        ->tool(XMeTool::class, [])
        ->assertHasNoErrors();
});
