<?php

namespace App\Mcp\Tools;

use App\Exceptions\XApiException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsOpenWorld]
#[Description('Get top-performing posts over a date range, sorted by likes, reposts, replies, quotes, bookmarks, impressions, or total engagement. Defaults to the connected user; pass a username to analyze any public account.')]
class XGetTopPostsTool extends XTool
{
    /**
     * Stop paginating after this many pages. At 100 tweets per page, this covers
     * the ~3,200-tweet ceiling X enforces on the user-timeline endpoint anyway.
     */
    private const MAX_PAGES = 40;

    private bool $isForeignScan = false;

    protected function creditCost(): int
    {
        $entry = config('credits.costs.XGetTopPostsTool', []);

        if (! is_array($entry)) {
            return (int) $entry;
        }

        if (! $this->isForeignScan && isset($entry['actions']['self'])) {
            return (int) $entry['actions']['self'];
        }

        return (int) ($entry['default'] ?? 10);
    }

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
            'username' => ['nullable', 'string', 'max:15', 'regex:/^@?[A-Za-z0-9_]+$/'],
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
        $username = isset($validated['username']) ? ltrim($validated['username'], '@') : null;

        if ($sortBy === 'impressions' && $username !== null) {
            return Response::error('Impressions are only available for the connected account. Choose likes, reposts, replies, quotes, bookmarks, or engagement when analyzing another user.');
        }

        $foreignMax = (int) config('credits.foreign_analytics_max_days', 180);

        if ($username !== null && $daysBack > $foreignMax) {
            return Response::error("Foreign account analytics are capped at {$foreignMax} days back to protect API costs. Analyze in shorter windows.");
        }

        $cutoff = Carbon::now()->subDays($daysBack);
        $this->isForeignScan = $username !== null;

        return $this->respond($request, function ($x) use ($request, $cutoff, $limit, $sortBy, $includeReplies, $includeReposts, $username) {
            $isSelf = $username === null;

            if ($isSelf) {
                $userId = $this->currentUserId($request);
                $resolvedHandle = null;
            } else {
                $lookup = $x->get('/users/by/username/'.$username);
                $userId = $lookup['data']['id'] ?? null;

                if ($userId === null) {
                    throw new XApiException("Could not find X user @{$username}.");
                }

                $resolvedHandle = $lookup['data']['username'] ?? $username;
            }

            $tweets = $this->fetchTweets($x, $userId, $cutoff, $includeReplies, $includeReposts, $isSelf);

            $sortField = self::SORT_KEYS[$sortBy];
            usort($tweets, fn (array $a, array $b) => $b[$sortField] <=> $a[$sortField]);

            return [
                'account' => $isSelf ? 'self' : '@'.$resolvedHandle,
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
    private function fetchTweets($x, string $userId, Carbon $cutoff, bool $includeReplies, bool $includeReposts, bool $isSelf): array
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

        // non_public_metrics is only available for the authenticated user's own tweets.
        $fields = $isSelf ? 'created_at,public_metrics,non_public_metrics' : 'created_at,public_metrics';

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $params = [
                'max_results' => 100,
                'tweet.fields' => $fields,
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
            'username' => $schema->string()->description('Optional @username to analyze. Omit to analyze the connected account. Impressions are only available for the connected account.'),
            'days_back' => $schema->integer()->description('How many days back to scan (1-365, default 30).'),
            'limit' => $schema->integer()->description('How many top posts to return (1-100, default 20).'),
            'sort_by' => $schema->string()->enum(array_keys(self::SORT_KEYS))->description('Which metric to rank by (default engagement).'),
            'include_replies' => $schema->boolean()->description('Include reply tweets (default false).'),
            'include_reposts' => $schema->boolean()->description('Include retweets (default false).'),
        ];
    }
}
