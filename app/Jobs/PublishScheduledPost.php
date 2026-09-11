<?php

namespace App\Jobs;

use App\Models\ScheduledPost;
use App\Services\X\XApiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PublishScheduledPost implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly ScheduledPost $scheduledPost) {}

    public function handle(XApiClient $x): void
    {
        $post = $this->scheduledPost;

        if (! $post->isPending()) {
            return;
        }

        $user = $post->user;
        $body = ['text' => $post->text];

        if (filled($post->reply_to_id)) {
            $body['reply'] = ['in_reply_to_tweet_id' => $post->reply_to_id];
        }

        if (filled($post->quote_id)) {
            $body['quote_tweet_id'] = $post->quote_id;
        }

        if (filled($post->media_ids)) {
            $body['media'] = ['media_ids' => array_values((array) $post->media_ids)];
        }

        $x->forUser($user)->post('/tweets', $body);

        $post->update(['published_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        $this->scheduledPost->update([
            'failed_at' => now(),
            'error' => $exception->getMessage(),
        ]);
    }
}
