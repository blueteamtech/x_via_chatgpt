<?php

it('suspends a user via user:suspend', function () {
    $user = connectedUser(['is_beta' => true, 'subscription_status' => 'active']);

    $this->artisan('user:suspend', ['id' => $user->id])->assertSuccessful();

    expect($user->fresh())
        ->subscription_status->toBe('suspended')
        ->is_beta->toBeFalse();
});

it('unsuspends a user via user:unsuspend', function () {
    $user = connectedUser(['is_beta' => false, 'subscription_status' => 'suspended']);

    $this->artisan('user:unsuspend', ['id' => $user->id])->assertSuccessful();

    expect($user->fresh()->subscription_status)->toBe('active');
});

it('sets a tier via user:tier', function () {
    $user = connectedUser();

    $this->artisan('user:tier', ['id' => $user->id, '--tier' => 'pro'])->assertSuccessful();

    expect($user->fresh())
        ->subscription_tier->toBe('pro')
        ->subscription_status->toBe('active');
});

it('removes a tier via user:tier --tier=none', function () {
    $user = connectedUser(['subscription_tier' => 'pro', 'subscription_status' => 'active']);

    $this->artisan('user:tier', ['id' => $user->id, '--tier' => 'none'])->assertSuccessful();

    expect($user->fresh())
        ->subscription_tier->toBeNull()
        ->subscription_status->toBeNull();
});

it('rejects an invalid tier', function () {
    $user = connectedUser();

    $this->artisan('user:tier', ['id' => $user->id, '--tier' => 'enterprise'])->assertFailed();

    expect($user->fresh()->subscription_tier)->toBeNull();
});
