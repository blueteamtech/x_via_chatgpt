<?php

beforeEach(function () {
    config([
        'stripe.webhook_secret' => 'whsec_test',
        'stripe.price_to_tier' => [
            'price_pub_test' => 'publisher',
            'price_pro_test' => 'pro',
        ],
    ]);
});

it('rejects webhooks without a valid signature', function () {
    $this->post('/stripe/webhook', ['type' => 'checkout.session.completed'])
        ->assertStatus(400);
});

it('rejects when no webhook secret is configured', function () {
    config(['stripe.webhook_secret' => null]);

    $this->post('/stripe/webhook', [])
        ->assertStatus(500);
});

it('activates a user on checkout.session.completed', function () {
    $user = connectedUser(['is_beta' => false, 'subscription_status' => null]);

    $payload = signedStripeEvent([
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'client_reference_id' => (string) $user->id,
                'customer' => 'cus_test123',
                'line_items' => [
                    'data' => [['price' => ['id' => 'price_pro_test']]],
                ],
            ],
        ],
    ]);

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $payload['signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $payload['body'])->assertOk();

    expect($user->fresh())
        ->subscription_tier->toBe('pro')
        ->subscription_status->toBe('active')
        ->stripe_customer_id->toBe('cus_test123');
});

it('marks a user past_due on invoice.payment_failed', function () {
    $user = connectedUser([
        'subscription_tier' => 'publisher',
        'subscription_status' => 'active',
        'stripe_customer_id' => 'cus_late',
    ]);

    $payload = signedStripeEvent([
        'type' => 'invoice.payment_failed',
        'data' => ['object' => ['customer' => 'cus_late']],
    ]);

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $payload['signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $payload['body'])->assertOk();

    expect($user->fresh()->subscription_status)->toBe('past_due');
});

it('marks a user cancelled on customer.subscription.deleted', function () {
    $user = connectedUser([
        'subscription_tier' => 'pro',
        'subscription_status' => 'active',
        'stripe_customer_id' => 'cus_leave',
    ]);

    $payload = signedStripeEvent([
        'type' => 'customer.subscription.deleted',
        'data' => ['object' => ['customer' => 'cus_leave']],
    ]);

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $payload['signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $payload['body'])->assertOk();

    expect($user->fresh()->subscription_status)->toBe('cancelled');
});

/**
 * Build a Stripe-formatted webhook payload with a valid signature for testing.
 *
 * @param  array<string, mixed>  $event
 * @return array{body: string, signature: string}
 */
function signedStripeEvent(array $event): array
{
    $event['id'] = 'evt_test_'.uniqid();
    $event['object'] = 'event';
    $event['created'] = time();

    $body = json_encode($event);
    $timestamp = time();
    $secret = config('stripe.webhook_secret');
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

    return [
        'body' => $body,
        'signature' => "t={$timestamp},v1={$signature}",
    ];
}
