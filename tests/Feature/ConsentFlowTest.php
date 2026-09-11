<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

function chatgptClient(string $redirectUri)
{
    return app(ClientRepository::class)
        ->createAuthorizationCodeGrantClient('ChatGPT', [$redirectUri], confidential: false);
}

function authorizeQuery(string $clientId, string $redirectUri): string
{
    return http_build_query([
        'response_type' => 'code',
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'scope' => 'mcp:use',
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
        'state' => 'chatgpt-state',
    ]);
}

it('issues an authorization code once the user approves', function () {
    $redirectUri = 'https://chatgpt.com/connector/oauth/test';
    $client = chatgptClient($redirectUri);
    $user = User::factory()->create(['username' => 'jess']);

    $this->actingAs($user)
        ->get('/oauth/authorize?'.authorizeQuery($client->getKey(), $redirectUri))
        ->assertOk()
        ->assertSee('Connect ChatGPT to X', false);

    $authToken = session('authToken');
    expect($authToken)->not->toBeEmpty();

    $response = $this->actingAs($user)->post('/oauth/authorize', [
        'auth_token' => $authToken,
        'state' => 'chatgpt-state',
        'client_id' => $client->getKey(),
    ]);

    $response->assertRedirect();
    $location = $response->headers->get('Location');

    expect($location)->toStartWith($redirectUri);
    expect($location)->toContain('code=');
    expect($location)->toContain('state=chatgpt-state');
});

it('rejects a resubmitted consent form because the token is single use', function () {
    $redirectUri = 'https://chatgpt.com/connector/oauth/test';
    $client = chatgptClient($redirectUri);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/oauth/authorize?'.authorizeQuery($client->getKey(), $redirectUri))
        ->assertOk();

    $authToken = session('authToken');

    $payload = [
        'auth_token' => $authToken,
        'state' => 'chatgpt-state',
        'client_id' => $client->getKey(),
    ];

    $this->actingAs($user)->post('/oauth/authorize', $payload)->assertRedirect();
    $this->actingAs($user)->post('/oauth/authorize', $payload)->assertForbidden();
});
