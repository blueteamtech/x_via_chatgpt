<?php

it('redirects guests through X sign-in', function () {
    $this->get('/billing')->assertRedirect(route('auth.x'));
});

it('sends users without a stripe customer id back to subscribe', function () {
    $user = connectedUser(['stripe_customer_id' => null]);

    $this->actingAs($user)
        ->get('/billing')
        ->assertRedirect(route('subscribe'));
});
