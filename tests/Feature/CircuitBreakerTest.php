<?php

use App\Exceptions\CircuitBreakerTrippedException;
use App\Services\Credits\CircuitBreaker;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::flush());

it('allows charges under the daily cap', function () {
    config(['credits.global_daily_max_credits' => 100]);

    $breaker = app(CircuitBreaker::class);
    $breaker->guard(50);
    $breaker->guard(40);

    expect($breaker->todaySpend())->toBe(90);
});

it('trips when the daily cap is exceeded', function () {
    config(['credits.global_daily_max_credits' => 100]);
    $breaker = app(CircuitBreaker::class);

    $breaker->guard(80);

    expect(fn () => $breaker->guard(30))->toThrow(CircuitBreakerTrippedException::class);
    // Failed charge did not count toward the daily total.
    expect($breaker->todaySpend())->toBe(80);
});
