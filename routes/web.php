<?php

use App\Http\Controllers\StartCheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\XAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', [
        'mcpUrl' => url('/mcp'),
        'callbackUrl' => url('/auth/x/callback'),
    ]);
})->name('home');

Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

Route::get('/login', fn () => redirect()->route('auth.x'))->name('login');

Route::get('/auth/x', [XAuthController::class, 'redirect'])->name('auth.x');
Route::get('/auth/x/callback', [XAuthController::class, 'callback'])->name('auth.x.callback');
Route::post('/logout', [XAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/subscribe', [SubscribeController::class, 'show'])
    ->middleware('auth')
    ->name('subscribe');

// One-click subscribe from landing page — signs the user in with X first if needed.
Route::get('/start/{tier}/{cadence?}', StartCheckoutController::class)
    ->middleware('auth')
    ->where('tier', 'publisher|pro|power')
    ->where('cadence', 'monthly|annual')
    ->name('start');

// Stripe webhook — CSRF excluded in bootstrap/app.php, verified via signature.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');
