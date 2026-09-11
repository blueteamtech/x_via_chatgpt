<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the connector home page', function () {
    $this->get('/')->assertOk()->assertSee('ChatGPT connector URL', false);
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
