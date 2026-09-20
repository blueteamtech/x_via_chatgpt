<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class StripePortalController
{
    /**
     * Send the current user to Stripe's Customer Portal to update card,
     * view invoices, cancel, or switch plans.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->stripe_customer_id) {
            return redirect()->route('subscribe')
                ->with('error', 'You do not have an active subscription to manage.');
        }

        $session = (new StripeClient(config('stripe.secret_key')))
            ->billingPortal
            ->sessions
            ->create([
                'customer' => $user->stripe_customer_id,
                'return_url' => route('subscribe'),
            ]);

        return redirect()->away($session->url);
    }
}
