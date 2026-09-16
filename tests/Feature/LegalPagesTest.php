<?php

it('serves the privacy policy at /privacy', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('OAuth');
});

it('serves the terms of service at /terms', function () {
    $this->get('/terms')
        ->assertOk()
        ->assertSee('Terms of Service')
        ->assertSee('Acceptable use');
});

it('links to legal pages from the homepage', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('privacy'))
        ->assertSee(route('terms'));
});
