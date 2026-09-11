<?php

return [

    /*
    |--------------------------------------------------------------------------
    | X Developer App
    |--------------------------------------------------------------------------
    |
    | Create these in the X Developer Portal. Laravel Cloud injects the same
    | names as environment variables — never commit real values.
    |
    */

    'client_id' => env('X_CLIENT_ID'),

    'client_secret' => env('X_CLIENT_SECRET'),

    'redirect' => env('X_REDIRECT_URI'),

    'authorize_url' => env('X_AUTHORIZE_URL', 'https://x.com/i/oauth2/authorize'),

    'token_url' => env('X_TOKEN_URL', 'https://api.x.com/2/oauth2/token'),

    'api_base' => env('X_API_BASE', 'https://api.x.com/2'),

    /*
    | User-context scopes ChatGPT can exercise after Sign in with X.
    | Enable the matching permissions on the X app itself.
    */
    'scopes' => [
        'tweet.read',
        'tweet.write',
        'tweet.moderate.write',
        'users.read',
        'follows.read',
        'follows.write',
        'like.read',
        'like.write',
        'list.read',
        'list.write',
        'mute.read',
        'mute.write',
        'block.read',
        'block.write',
        'bookmark.read',
        'bookmark.write',
        'dm.read',
        'dm.write',
        'media.write',
        'offline.access',
    ],

];
