<?php

it('redirects guests through X sign-in first', function () {
    $this->get('/start/publisher/monthly')->assertRedirect(route('auth.x'));
});

it('redirects signed-in users straight to Stripe with their user id attached', function () {
    config(['stripe.payment_links.pro_monthly' => 'https://buy.stripe.com/pro-monthly']);
    $user = connectedUser();

    $this->actingAs($user)
        ->get('/start/pro/monthly')
        ->assertRedirectContains('https://buy.stripe.com/pro-monthly')
        ->assertRedirectContains('client_reference_id='.$user->id);
});

it('rejects an unknown tier or cadence', function () {
    $user = connectedUser();

    $this->actingAs($user)->get('/start/nothing/monthly')->assertNotFound();
    $this->actingAs($user)->get('/start/pro/lifetime')->assertNotFound();
});

it('returns 404 if the payment link is not configured for a valid tier', function () {
    config(['stripe.payment_links.publisher_monthly' => null]);
    $user = connectedUser();

    $this->actingAs($user)->get('/start/publisher/monthly')->assertNotFound();
});
