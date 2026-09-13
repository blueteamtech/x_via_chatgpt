<?php

namespace App\Mcp\Tools;

use App\Exceptions\XApiException;
use App\Models\ToolInvocation;
use App\Models\User;
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
            $result = $callback($this->client($request));

            return Response::json($result);
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
