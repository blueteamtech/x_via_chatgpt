<?php

use App\Exceptions\CreditsExhaustedException;
use App\Services\Credits\CreditManager;

it('does not cap grandfathered users (null tier)', function () {
    $user = connectedUser(['subscription_tier' => null]);

    app(CreditManager::class)->charge($user, 999999);

    expect($user->fresh()->credits_used_this_month)->toBe(999999);
});

it('caps publisher tier at 1000 credits', function () {
    $user = connectedUser(['subscription_tier' => 'publisher']);
    $manager = app(CreditManager::class);

    $manager->charge($user, 999);
    expect(fn () => $manager->charge($user, 2))->toThrow(CreditsExhaustedException::class);
});

it('caps pro tier at 3000 credits', function () {
    $user = connectedUser(['subscription_tier' => 'pro']);
    $manager = app(CreditManager::class);

    $manager->charge($user, 2999);
    expect(fn () => $manager->charge($user, 2))->toThrow(CreditsExhaustedException::class);
});

it('caps power tier at 10000 credits', function () {
    $user = connectedUser(['subscription_tier' => 'power']);
    $manager = app(CreditManager::class);

    $manager->charge($user, 9999);
    expect(fn () => $manager->charge($user, 2))->toThrow(CreditsExhaustedException::class);
});

it('rolls over the counter on a new month', function () {
    $user = connectedUser([
        'subscription_tier' => 'publisher',
        'credits_used_this_month' => 950,
        'credits_reset_at' => now()->subMonth()->startOfMonth(),
    ]);

    app(CreditManager::class)->charge($user, 100);

    // Because rollover kicked in, only 100 was charged (not 1050).
    expect($user->fresh()->credits_used_this_month)->toBe(100);
});

it('reports status for x-me consumption', function () {
    $user = connectedUser([
        'subscription_tier' => 'pro',
        'credits_used_this_month' => 450,
        'credits_reset_at' => now()->startOfMonth(),
    ]);

    $status = app(CreditManager::class)->status($user);

    expect($status['tier'])->toBe('pro')
        ->and($status['used'])->toBe(450)
        ->and($status['allowance'])->toBe(3000)
        ->and($status['remaining'])->toBe(2550);
});
