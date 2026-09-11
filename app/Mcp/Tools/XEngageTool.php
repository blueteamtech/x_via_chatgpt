<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Like, unlike, repost, undo a repost, bookmark, or unbookmark a post as the connected X user.')]
class XEngageTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'action' => ['required', 'in:like,unlike,repost,unrepost,bookmark,unbookmark'],
            'post_id' => ['required', 'string'],
        ]);

        return $this->respond($request, function ($x) use ($request, $validated) {
            $userId = $this->currentUserId($request);
            $postId = $validated['post_id'];

            return match ($validated['action']) {
                'like' => $x->post("/users/{$userId}/likes", ['tweet_id' => $postId]),
                'unlike' => $x->delete("/users/{$userId}/likes/{$postId}"),
                'repost' => $x->post("/users/{$userId}/retweets", ['tweet_id' => $postId]),
                'unrepost' => $x->delete("/users/{$userId}/retweets/{$postId}"),
                'bookmark' => $x->post("/users/{$userId}/bookmarks", ['tweet_id' => $postId]),
                'unbookmark' => $x->delete("/users/{$userId}/bookmarks/{$postId}"),
            };
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(['like', 'unlike', 'repost', 'unrepost', 'bookmark', 'unbookmark'])->required(),
            'post_id' => $schema->string()->description('Target post id.')->required(),
        ];
    }
}
