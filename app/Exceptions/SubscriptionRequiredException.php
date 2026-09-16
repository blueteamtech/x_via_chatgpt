<?php

namespace App\Exceptions;

use RuntimeException;

class SubscriptionRequiredException extends RuntimeException
{
    public function __construct(string $reason = 'not_subscribed')
    {
        $message = match ($reason) {
            'suspended' => 'Your account is suspended. Email cyberandchill@gmail.com to resolve.',
            'past_due' => 'Your subscription payment failed. Update your card in the Stripe portal to restore access.',
            'cancelled' => 'Your subscription was cancelled. Resubscribe at your account page to restore access.',
            default => 'This feature requires an active subscription. Visit '.config('app.url').'/subscribe to choose a plan.',
        };

        parent::__construct($message);
    }
}
