<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Per-tier monthly credit allowances
    |--------------------------------------------------------------------------
    | A null tier bypasses credit checks entirely (grandfathered / beta / owner).
    */

    'tiers' => [
        'publisher' => 1000,
        'pro' => 3000,
        'power' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit cost per tool invocation
    |--------------------------------------------------------------------------
    | Keyed by tool basename. Some tools have per-action overrides via 'actions'.
    | Costs approximate X API $ costs at ~1 credit = 1 cent, plus small markup.
    */

    'costs' => [
        'XMeTool' => 0,

        'XReadFeedTool' => [
            'default' => 2, // foreign timeline / search / post read
            'actions' => [
                'home' => 1, // own mentions/timeline (cheap owned reads)
                'mentions' => 1,
            ],
        ],

        'XGetTopPostsTool' => [
            'default' => 10, // foreign account scan (paginated foreign reads)
            'actions' => [
                'self' => 3, // own account scan (owned reads are cheap)
            ],
        ],

        'XUserLookupTool' => 2,

        'XCreatePostTool' => [
            'default' => 2, // no-link post
            'actions' => [
                'link' => 22, // has_link → 13x cost on X
            ],
        ],

        'XCreateThreadTool' => 2, // per post in thread

        'XSchedulePostTool' => 0, // scheduling is free; publish charges at fire time

        'XDeletePostTool' => 1,

        'XEngageTool' => 1,
        'XSocialTool' => 1,
        'XListsTool' => 1,

        'XDirectMessagesTool' => [
            'default' => 2,
            'actions' => [
                'list' => 2,
            ],
        ],

        'XUploadMediaTool' => 1,

        'XArticlesTool' => [
            'default' => 0,
            'actions' => [
                'draft' => 3,
                'publish' => 2,
                'update' => 3,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hard caps (safety rails)
    |--------------------------------------------------------------------------
    */

    // Max posts in a single thread (create or scheduled).
    'thread_max_posts' => 25,

    // Max days_back allowed when analyzing a FOREIGN account.
    // Own account analytics is unlimited (owned reads are cheap).
    'foreign_analytics_max_days' => 180,

    // Max DMs sent per user per calendar day (X anti-spam).
    'dm_daily_max' => 20,

    // If a user sends the exact same DM text to this many recipients in 24hr,
    // further sends with that text are blocked (spam-ban protection for our app).
    'dm_identical_content_max_recipients' => 3,

    /*
    |--------------------------------------------------------------------------
    | Global daily circuit breaker
    |--------------------------------------------------------------------------
    | If total credits deducted across ALL users on a given day exceeds this
    | threshold, tools are paused until midnight UTC. Prevents runaway spend
    | from any source (bugs, abuse, coordinated activity).
    |
    | Credits ≈ pennies of X API cost. 500 credits/day ≈ $5/day ≈ $150/mo.
    */

    'global_daily_max_credits' => env('CREDITS_DAILY_CIRCUIT_BREAKER', 500),

];
