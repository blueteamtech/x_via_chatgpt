<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscribeController
{
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $links = config('stripe.payment_links');

        // Append user id to every payment link so the webhook can identify
        // which XConnect user completed the checkout.
        $withRef = collect($links)->map(function (?string $url) use ($user): ?string {
            if (! $url) {
                return null;
            }

            $separator = str_contains($url, '?') ? '&' : '?';

            return $url.$separator.'client_reference_id='.$user->id;
        })->all();

        return view('subscribe', [
            'user' => $user,
            'links' => $withRef,
            'currentTier' => $user->subscription_tier,
            'currentStatus' => $user->subscription_status,
        ]);
    }
}
