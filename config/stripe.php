<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe API keys
    |--------------------------------------------------------------------------
    | Set in .env for local dev, in Laravel Cloud env vars for production.
    | NEVER commit secret keys.
    */

    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Payment Link URLs — one per tier
    |--------------------------------------------------------------------------
    | Create in Stripe dashboard → Products → Payment Links. Each Payment Link
    | should have subscription pricing that matches the tier. Copy the URL
    | Stripe gives you into the corresponding env var below.
    |
    | We append ?client_reference_id={user_id} at click time so the webhook
    | knows which XConnect user completed the purchase.
    */

    'payment_links' => [
        'publisher_monthly' => env('STRIPE_LINK_PUBLISHER_MONTHLY'),
        'publisher_annual' => env('STRIPE_LINK_PUBLISHER_ANNUAL'),
        'pro_monthly' => env('STRIPE_LINK_PRO_MONTHLY'),
        'pro_annual' => env('STRIPE_LINK_PRO_ANNUAL'),
        'power_monthly' => env('STRIPE_LINK_POWER_MONTHLY'),
        'power_annual' => env('STRIPE_LINK_POWER_ANNUAL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Product / price id → XConnect tier
    |--------------------------------------------------------------------------
    | When Stripe sends a webhook, we look up which of our tiers the paid
    | price matches so we know how many credits to grant. Fill these in
    | after creating the Products in Stripe.
    */

    'price_to_tier' => [
        // 'price_xxxxx' => 'publisher',
        // 'price_xxxxx' => 'pro',
        // 'price_xxxxx' => 'power',
    ],

];
