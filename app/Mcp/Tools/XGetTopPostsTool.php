<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsOpenWorld]
#[Description('Get the connected user\'s top-performing posts over a date range, sorted by likes, reposts, replies, quotes, bookmarks, impressions, or total engagement.')]
class XGetTopPostsTool extends XTool
{
    /**
     * Stop paginating after this many pages. At 100 tweets per page, this covers
     * the ~3,200-tweet ceiling X enforces on the user-timeline endpoint anyway.
     */
    private const MAX_PAGES = 40;

    /**
     * @var array<string, string> sort keys → tweet field they map to
     */
    private const SORT_KEYS = [
        'likes' => 'like_count',
        'reposts' => 'retweet_count',
        'replies' => 'reply_count',
        'quotes' => 'quote_count',
        'bookmarks' => 'bookmark_count',
        'impressions' => 'impression_count',
        'engagement' => 'engagement',
    ];

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'days_back' => ['nullable', 'integer', 'min:1', 'max:365'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:likes,reposts,replies,quotes,bookmarks,impressions,engagement'],
            'include_replies' => ['nullable', 'boolean'],
            'include_reposts' => ['nullable', 'boolean'],
        ]);

        $daysBack = $validated['days_back'] ?? 30;
        $limit = $validated['limit'] ?? 20;
        $sortBy = $validated['sort_by'] ?? 'engagement';
        $includeReplies = $validated['include_replies'] ?? false;
        $includeReposts = $validated['include_reposts'] ?? false;

        $cutoff = Carbon::now()->subDays($daysBack);

        return $this->respond($request, function ($x) use ($request, $cutoff, $limit, $sortBy, $includeReplies, $includeReposts) {
            $userId = $this->currentUserId($request);
            $tweets = $this->fetchTweets($x, $userId, $cutoff, $includeReplies, $includeReposts);

            $sortField = self::SORT_KEYS[$sortBy];
            usort($tweets, fn (array $a, array $b) => $b[$sortField] <=> $a[$sortField]);

            return [
                'sorted_by' => $sortBy,
                'window_from' => $cutoff->toIso8601String(),
                'window_to' => now()->toIso8601String(),
                'tweets_scanned' => count($tweets),
                'top_posts' => array_slice($tweets, 0, $limit),
            ];
        });
    }

    /**
     * Walk the user's timeline until we hit the cutoff, capping at MAX_PAGES.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchTweets($x, string $userId, Carbon $cutoff, bool $includeReplies, bool $includeReposts): array
    {
        $tweets = [];
        $nextToken = null;
        $exclude = [];

        if (! $includeReplies) {
            $exclude[] = 'replies';
        }

        if (! $includeReposts) {
            $exclude[] = 'retweets';
        }

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $params = [
                'max_results' => 100,
                'tweet.fields' => 'created_at,public_metrics,non_public_metrics',
            ];

            if ($exclude !== []) {
                $params['exclude'] = implode(',', $exclude);
            }

            if ($nextToken !== null) {
                $params['pagination_token'] = $nextToken;
            }

            $response = $x->get("/users/{$userId}/tweets", $params);
            $reachedCutoff = false;

            foreach ($response['data'] ?? [] as $tweet) {
                $createdAt = Carbon::parse($tweet['created_at']);

                if ($createdAt->lt($cutoff)) {
                    $reachedCutoff = true;

                    continue;
                }

                $tweets[] = $this->flatten($tweet);
            }

            $nextToken = $response['meta']['next_token'] ?? null;

            if ($reachedCutoff || $nextToken === null) {
                break;
            }
        }

        return $tweets;
    }

    /**
     * @param  array<string, mixed>  $tweet
     * @return array<string, mixed>
     */
    private function flatten(array $tweet): array
    {
        $public = $tweet['public_metrics'] ?? [];
        $nonPublic = $tweet['non_public_metrics'] ?? [];

        $likes = (int) ($public['like_count'] ?? 0);
        $reposts = (int) ($public['retweet_count'] ?? 0);
        $replies = (int) ($public['reply_count'] ?? 0);
        $quotes = (int) ($public['quote_count'] ?? 0);
        $bookmarks = (int) ($public['bookmark_count'] ?? 0);
        $impressions = (int) ($nonPublic['impression_count'] ?? $public['impression_count'] ?? 0);

        return [
            'id' => $tweet['id'],
            'text' => $tweet['text'] ?? '',
            'created_at' => $tweet['created_at'],
            'like_count' => $likes,
            'retweet_count' => $reposts,
            'reply_count' => $replies,
            'quote_count' => $quotes,
            'bookmark_count' => $bookmarks,
            'impression_count' => $impressions,
            'engagement' => $likes + $reposts + $replies + $quotes + $bookmarks,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days_back' => $schema->integer()->description('How many days back to scan (1-365, default 30).'),
            'limit' => $schema->integer()->description('How many top posts to return (1-100, default 20).'),
            'sort_by' => $schema->string()->enum(array_keys(self::SORT_KEYS))->description('Which metric to rank by (default engagement).'),
            'include_replies' => $schema->boolean()->description('Include reply tweets (default false).'),
            'include_reposts' => $schema->boolean()->description('Include retweets (default false).'),
        ];
    }
}
