<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Throwable;
use UnexpectedValueException;

class StripeWebhookController
{
    public function handle(Request $request): Response
    {
        $secret = config('stripe.webhook_secret');

        if (! $secret) {
            Log::error('stripe.webhook.no_secret_configured');

            return response('webhook secret not configured', 500);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret,
            );
        } catch (UnexpectedValueException $e) {
            return response('invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            return response('invalid signature', 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->onCheckoutCompleted($event),
            'customer.subscription.updated' => $this->onSubscriptionUpdated($event),
            'customer.subscription.deleted' => $this->onSubscriptionDeleted($event),
            'invoice.payment_failed' => $this->onPaymentFailed($event),
            default => Log::info('stripe.webhook.unhandled', ['type' => $event->type]),
        };

        return response('ok', 200);
    }

    private function onCheckoutCompleted(Event $event): void
    {
        $session = $event->data->object;
        $userId = $session->client_reference_id ?? null;
        $priceId = $this->extractPriceId($session);

        if (! $userId || ! $priceId) {
            Log::warning('stripe.webhook.missing_ids', [
                'user_id' => $userId,
                'price_id' => $priceId,
            ]);

            return;
        }

        $tier = config('stripe.price_to_tier.'.$priceId);

        if (! $tier) {
            Log::warning('stripe.webhook.unmapped_price', ['price_id' => $priceId]);

            return;
        }

        $user = User::find($userId);

        if (! $user) {
            Log::warning('stripe.webhook.user_not_found', ['user_id' => $userId]);

            return;
        }

        $user->forceFill([
            'subscription_tier' => $tier,
            'subscription_status' => 'active',
            'subscription_started_at' => $user->subscription_started_at ?? now(),
            'stripe_customer_id' => $session->customer ?? null,
            'is_beta' => false, // paid subscribers no longer need beta bypass
            'credits_used_this_month' => 0,
            'credits_reset_at' => now()->startOfMonth(),
        ])->save();

        Log::info('stripe.webhook.subscription_activated', [
            'user_id' => $user->id,
            'tier' => $tier,
        ]);
    }

    private function onSubscriptionUpdated(Event $event): void
    {
        $subscription = $event->data->object;
        $customerId = $subscription->customer ?? null;

        if (! $customerId) {
            return;
        }

        $user = User::where('stripe_customer_id', $customerId)->first();

        if (! $user) {
            return;
        }

        $status = $this->mapStripeStatus($subscription->status ?? '');
        $user->forceFill(['subscription_status' => $status])->save();
    }

    private function onSubscriptionDeleted(Event $event): void
    {
        $subscription = $event->data->object;
        $customerId = $subscription->customer ?? null;

        if (! $customerId) {
            return;
        }

        User::where('stripe_customer_id', $customerId)
            ->update(['subscription_status' => 'cancelled']);
    }

    private function onPaymentFailed(Event $event): void
    {
        $invoice = $event->data->object;
        $customerId = $invoice->customer ?? null;

        if (! $customerId) {
            return;
        }

        User::where('stripe_customer_id', $customerId)
            ->update(['subscription_status' => 'past_due']);
    }

    private function extractPriceId(object $session): ?string
    {
        // Stripe does NOT include line_items in webhook payloads by default,
        // so we look up the subscription (always present for subscription-mode
        // checkouts, which is what Payment Links create for recurring prices)
        // and read the price from its first item.
        $subscriptionId = $session->subscription ?? null;

        if ($subscriptionId) {
            try {
                $subscription = $this->stripe()->subscriptions->retrieve($subscriptionId);
                $priceId = $subscription->items->data[0]->price->id ?? null;

                if ($priceId) {
                    return $priceId;
                }
            } catch (Throwable $e) {
                Log::warning('stripe.webhook.subscription_lookup_failed', [
                    'subscription' => $subscriptionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback for one-off Payment Link checkouts that expand line_items,
        // and for the "put price_id in metadata" pattern used in some tests.
        if (isset($session->line_items->data[0]->price->id)) {
            return $session->line_items->data[0]->price->id;
        }

        return $session->metadata->price_id ?? null;
    }

    private function stripe(): StripeClient
    {
        return new StripeClient(config('stripe.secret_key'));
    }

    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active', 'trialing' => 'active',
            'past_due', 'unpaid' => 'past_due',
            'canceled', 'incomplete_expired' => 'cancelled',
            default => 'inactive',
        };
    }
}
