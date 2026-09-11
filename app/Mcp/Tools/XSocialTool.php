<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Follow, unfollow, block, unblock, mute, or unmute an X user as the connected account.')]
class XSocialTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'action' => ['required', 'in:follow,unfollow,block,unblock,mute,unmute'],
            'target_user_id' => ['required', 'string'],
        ]);

        return $this->respond($request, function ($x) use ($request, $validated) {
            $userId = $this->currentUserId($request);
            $target = $validated['target_user_id'];

            return match ($validated['action']) {
                'follow' => $x->post("/users/{$userId}/following", ['target_user_id' => $target]),
                'unfollow' => $x->delete("/users/{$userId}/following/{$target}"),
                'block' => $x->post("/users/{$userId}/blocking", ['target_user_id' => $target]),
                'unblock' => $x->delete("/users/{$userId}/blocking/{$target}"),
                'mute' => $x->post("/users/{$userId}/muting", ['target_user_id' => $target]),
                'unmute' => $x->delete("/users/{$userId}/muting/{$target}"),
            };
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(['follow', 'unfollow', 'block', 'unblock', 'mute', 'unmute'])->required(),
            'target_user_id' => $schema->string()->description('The X user id to act on.')->required(),
        ];
    }
}
