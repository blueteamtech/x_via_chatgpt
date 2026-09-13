<?php

namespace App\Mcp\Tools;

use App\Exceptions\XApiException;
use App\Services\X\ThreadSplitter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Post a thread on X. Accepts either a pre-split array of post texts, or a single long text that will be auto-split at ~275 char boundaries. Each post replies to the previous one.')]
class XCreateThreadTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'posts' => ['nullable', 'array'],
            'posts.*' => ['string', 'max:4000'],
            'reply_to_id' => ['nullable', 'string'],
        ]);

        if (empty($validated['text']) && empty($validated['posts'])) {
            return Response::error('Pass either a text (to auto-split) or a posts array.');
        }

        $posts = $validated['posts'] ?? app(ThreadSplitter::class)->split($validated['text']);

        if ($posts === []) {
            return Response::error('Nothing to post.');
        }

        return $this->respond($request, function ($x) use ($posts, $validated) {
            $created = [];
            $replyTo = $validated['reply_to_id'] ?? null;

            foreach ($posts as $index => $text) {
                $body = ['text' => $text];

                if ($replyTo !== null) {
                    $body['reply'] = ['in_reply_to_tweet_id' => $replyTo];
                }

                try {
                    $response = $x->post('/tweets', $body);
                } catch (XApiException $exception) {
                    throw new XApiException(
                        'Thread stopped at post '.($index + 1).'/'.count($posts).': '.$exception->getMessage(),
                        $exception->status,
                        $exception->payload,
                    );
                }

                $id = $response['data']['id'] ?? null;
                $created[] = ['position' => $index + 1, 'id' => $id, 'text' => $text];
                $replyTo = $id;
            }

            return [
                'thread_length' => count($created),
                'root_post_id' => $created[0]['id'] ?? null,
                'posts' => $created,
            ];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->description('One long text to auto-split into a thread. Use this OR posts.'),
            'posts' => $schema->array()->description('Pre-split array of post texts, in order. Use this OR text.'),
            'reply_to_id' => $schema->string()->description('Optional: make the whole thread a reply to this post id.'),
        ];
    }
}
