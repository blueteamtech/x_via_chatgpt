<?php

it('redirects guests to X sign-in', function () {
    $this->get('/subscribe')->assertRedirect(route('auth.x'));
});

it('shows the three tiers to signed-in users', function () {
    config([
        'stripe.payment_links.publisher_monthly' => 'https://buy.stripe.com/pub',
        'stripe.payment_links.pro_monthly' => 'https://buy.stripe.com/pro',
        'stripe.payment_links.power_monthly' => 'https://buy.stripe.com/pow',
    ]);

    $user = connectedUser();

    $this->actingAs($user)
        ->get('/subscribe')
        ->assertOk()
        ->assertSee('Publisher')
        ->assertSee('Pro')
        ->assertSee('Power');
});

it('appends client_reference_id to every payment link', function () {
    config(['stripe.payment_links.pro_monthly' => 'https://buy.stripe.com/pro']);

    $user = connectedUser();

    $this->actingAs($user)
        ->get('/subscribe')
        ->assertSee('client_reference_id='.$user->id);
});

it('flags an active subscriber with a status badge', function () {
    $user = connectedUser(['subscription_tier' => 'pro', 'subscription_status' => 'active']);

    $this->actingAs($user)
        ->get('/subscribe')
        ->assertOk()
        ->assertSee('on the <strong>Pro</strong>', false);
});
