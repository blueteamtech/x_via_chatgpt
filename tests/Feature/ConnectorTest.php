<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

it('shows the landing page with the connector URL and pricing tiers', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Publisher')
        ->assertSee('Pro')
        ->assertSee('Power')
        ->assertSee('/mcp', false);
});

it('exposes the Laravel Cloud health endpoint', function () {
    $this->get('/up')->assertOk();
});

it('publishes ChatGPT OAuth discovery documents', function () {
    $this->get('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonPath('grant_types_supported.0', 'authorization_code')
        ->assertJsonPath('code_challenge_methods_supported.0', 'S256');

    $this->get('/.well-known/oauth-protected-resource')
        ->assertOk()
        ->assertJsonStructure(['resource', 'authorization_servers', 'scopes_supported']);

    $this->get('/.well-known/oauth-authorization-server/mcp')->assertOk();
    $this->get('/.well-known/oauth-protected-resource/mcp')->assertOk();
});

it('sends guests from the ChatGPT consent screen to Sign in with X', function () {
    $redirectUri = 'https://chatgpt.com/connector/oauth/test';

    $client = app(ClientRepository::class)
        ->createAuthorizationCodeGrantClient('ChatGPT', [$redirectUri], confidential: false);

    $this->get('/oauth/authorize?'.http_build_query([
        'response_type' => 'code',
        'client_id' => $client->getKey(),
        'redirect_uri' => $redirectUri,
        'scope' => 'mcp:use',
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
        'state' => 'test-state',
    ]))->assertRedirect(route('auth.x'));
});

it('still returns JSON for the machine OAuth endpoints', function () {
    $this->post('/oauth/token')->assertJson(fn ($json) => $json->etc());
});

it('rejects unauthenticated MCP posts', function () {
    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'test', 'version' => '1'],
        ],
    ])->assertUnauthorized();
});
