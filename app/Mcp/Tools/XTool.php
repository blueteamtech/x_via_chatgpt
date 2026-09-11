<?php

namespace App\Mcp\Tools;

use App\Exceptions\XApiException;
use App\Models\User;
use App\Services\X\XApiClient;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

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
        try {
            $result = $callback($this->client($request));

            return Response::json($result);
        } catch (XApiException $exception) {
            return Response::error($exception->getMessage());
        }
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
