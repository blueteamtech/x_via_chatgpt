<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('explains how to collect X app credentials when they are missing', function () {
    config([
        'x.client_id' => null,
        'x.client_secret' => null,
    ]);

    $this->get('/auth/x')
        ->assertOk()
        ->assertSee('X_CLIENT_ID');
});

it('redirects to X authorize when developer credentials are present', function () {
    config([
        'x.client_id' => 'test-client-id',
        'x.client_secret' => 'test-client-secret',
        'x.redirect' => 'http://localhost/auth/x/callback',
        'app.url' => 'http://localhost',
    ]);

    $this->get('/auth/x')
        ->assertRedirect()
        ->assertRedirectContains('https://x.com/i/oauth2/authorize');
});
