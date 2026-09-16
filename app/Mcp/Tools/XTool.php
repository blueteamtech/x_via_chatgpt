<?php

namespace App\Mcp\Tools;

use App\Exceptions\CircuitBreakerTrippedException;
use App\Exceptions\CreditsExhaustedException;
use App\Exceptions\SubscriptionRequiredException;
use App\Exceptions\XApiException;
use App\Models\ToolInvocation;
use App\Models\User;
use App\Services\Credits\CircuitBreaker;
use App\Services\Credits\CreditManager;
use App\Services\X\XApiClient;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;

abstract class XTool extends Tool
{
    public function __construct(protected XApiClient $x) {}

    protected function client(Request $request): XApiClient
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new XApiException('Sign in with X from ChatGPT before using this tool.');
        }

        return $this->x->forUser($user);
    }

    /**
     * @param  callable(XApiClient): mixed  $callback
     */
    protected function respond(Request $request, callable $callback): Response
    {
        $start = hrtime(true);
        $user = $request->user();
        $error = null;
        $success = true;

        try {
            $this->ensureSubscribed($user);

            $result = $callback($this->client($request));

            $this->chargeCredits($user);

            return Response::json($result);
        } catch (SubscriptionRequiredException|CreditsExhaustedException|CircuitBreakerTrippedException $exception) {
            $success = false;
            $error = $exception->getMessage();

            return Response::error($exception->getMessage());
        } catch (XApiException $exception) {
            $success = false;
            $error = $exception->getMessage();

            return Response::error($this->errorMessage($exception));
        } catch (Throwable $exception) {
            $success = false;
            $error = $exception->getMessage();

            throw $exception;
        } finally {
            $this->recordInvocation($user, $success, $error, (int) ((hrtime(true) - $start) / 1_000_000));
        }
    }

    /**
     * Block the tool call for users without a valid subscription.
     * Beta users (grandfathered) and active subscribers pass through.
     * Individual tools may override skipSubscriptionCheck() to always allow
     * (e.g. x-me lets users check their own status while blocked).
     */
    protected function ensureSubscribed(?User $user): void
    {
        if (! $user instanceof User || $this->skipSubscriptionCheck()) {
            return;
        }

        if ($user->is_beta) {
            return;
        }

        $status = $user->subscription_status;

        if ($status === 'active') {
            return;
        }

        throw new SubscriptionRequiredException($status ?? 'not_subscribed');
    }

    /**
     * Override in tools that must remain usable regardless of subscription
     * (e.g. x-me for checking account state).
     */
    protected function skipSubscriptionCheck(): bool
    {
        return false;
    }

    /**
     * Charge the user for a successful tool call. Static per-tool cost is
     * defined in config/credits.php; individual tools may override
     * creditCost() to compute dynamic per-invocation costs.
     */
    protected function chargeCredits(?User $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        $credits = $this->creditCost();

        if ($credits <= 0) {
            return;
        }

        app(CircuitBreaker::class)->guard($credits);
        app(CreditManager::class)->charge($user, $credits);
    }

    /**
     * Default credit cost = the tool's static cost from config.
     * Override in subclasses for per-action or per-invocation dynamic costs.
     */
    protected function creditCost(): int
    {
        $entry = config('credits.costs.'.class_basename(static::class), 0);

        return is_array($entry) ? (int) ($entry['default'] ?? 0) : (int) $entry;
    }

    /**
     * Fire-and-forget usage log. Never allow the log write to break a tool call.
     */
    private function recordInvocation(?User $user, bool $success, ?string $error, int $durationMs): void
    {
        try {
            ToolInvocation::create([
                'user_id' => $user?->getKey(),
                'tool' => class_basename(static::class),
                'success' => $success,
                'duration_ms' => $durationMs,
                'error' => $error !== null ? mb_substr($error, 0, 500) : null,
            ]);
        } catch (Throwable) {
            // Logging must never break the tool itself.
        }
    }

    /**
     * Keep X's status code in the error text so the model can tell a rate limit
     * from a bad argument and decide whether retrying is worth it.
     */
    protected function errorMessage(XApiException $exception): string
    {
        return match (true) {
            $exception->status === 429 => 'X rate limit reached — wait before trying again. '.$exception->getMessage(),
            $exception->status > 0 => "X API error {$exception->status}: {$exception->getMessage()}",
            default => $exception->getMessage(),
        };
    }

    protected function currentUserId(Request $request): string
    {
        $user = $request->user();

        if (! $user instanceof User || blank($user->x_id)) {
            throw new XApiException('The connected X user id is missing. Sign in with X again.');
        }

        return (string) $user->x_id;
    }
}
