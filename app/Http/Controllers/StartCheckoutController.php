<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StartCheckoutController
{
    /**
     * One-click subscribe: click a tier button anywhere on the site and end up
     * on Stripe checkout — signing in with X first if needed.
     */
    public function __invoke(Request $request, string $tier, string $cadence = 'monthly'): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $key = strtolower($tier).'_'.strtolower($cadence);
        $url = config('stripe.payment_links.'.$key);

        if (! $url) {
            abort(404, 'Unknown plan.');
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return redirect()->away($url.$separator.'client_reference_id='.$user->id);
    }
}
