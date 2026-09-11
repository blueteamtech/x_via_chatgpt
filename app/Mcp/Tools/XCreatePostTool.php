<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Create a post on the connected X account. Can reply or quote by passing those post ids.')]
class XCreatePostTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:4000'],
            'reply_to_id' => ['nullable', 'string'],
            'quote_id' => ['nullable', 'string'],
            'media_ids' => ['nullable'],
        ]);

        $mediaIds = $validated['media_ids'] ?? [];

        if (is_string($mediaIds)) {
            $mediaIds = array_values(array_filter(array_map('trim', explode(',', $mediaIds))));
        }

        $body = ['text' => $validated['text']];

        if (! empty($validated['reply_to_id'])) {
            $body['reply'] = ['in_reply_to_tweet_id' => $validated['reply_to_id']];
        }

        if (! empty($validated['quote_id'])) {
            $body['quote_tweet_id'] = $validated['quote_id'];
        }

        if ($mediaIds !== []) {
            $body['media'] = ['media_ids' => array_values($mediaIds)];
        }

        return $this->respond($request, fn ($x) => $x->post('/tweets', $body));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->description('The post text.')->required(),
            'reply_to_id' => $schema->string()->description('Optional post id to reply to.'),
            'quote_id' => $schema->string()->description('Optional post id to quote.'),
            'media_ids' => $schema->string()->description('Optional comma-separated media ids from x-upload-media. Prefer repeating this tool after upload.'),
        ];
    }
}
