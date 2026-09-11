<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsOpenWorld]
#[Description('Read the connected account home timeline, mentions, a post by id, or recent search.')]
class XReadFeedTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'view' => ['required', 'in:home,mentions,search,post'],
            'query' => ['required_if:view,search', 'nullable', 'string', 'max:512'],
            'post_id' => ['required_if:view,post', 'nullable', 'string'],
            'max_results' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $max = $validated['max_results'] ?? 20;
        $fields = [
            'tweet.fields' => 'created_at,public_metrics,lang,author_id,conversation_id',
            'expansions' => 'author_id',
            'user.fields' => 'name,username',
        ];

        return $this->respond($request, function ($x) use ($request, $validated, $max, $fields) {
            $userId = $this->currentUserId($request);

            return match ($validated['view']) {
                'home' => $x->get("/users/{$userId}/timelines/reverse_chronological", [
                    ...$fields,
                    'max_results' => $max,
                ]),
                'mentions' => $x->get("/users/{$userId}/mentions", [
                    ...$fields,
                    'max_results' => $max,
                ]),
                'post' => $x->get('/tweets/'.$validated['post_id'], $fields),
                'search' => $x->get('/tweets/search/recent', [
                    ...$fields,
                    'query' => $validated['query'],
                    'max_results' => $max,
                ]),
            };
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'view' => $schema->string()->enum(['home', 'mentions', 'search', 'post'])->description('What to read.')->required(),
            'query' => $schema->string()->description('Required when view is search. X recent-search query.'),
            'post_id' => $schema->string()->description('Required when view is post.'),
            'max_results' => $schema->integer()->description('How many posts to return (5-100).'),
        ];
    }
}
