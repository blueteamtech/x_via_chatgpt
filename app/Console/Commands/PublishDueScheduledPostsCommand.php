<?php

namespace App\Console\Commands;

use App\Exceptions\XApiException;
use App\Models\ScheduledPost;
use App\Services\X\XApiClient;
use Illuminate\Console\Command;
use Throwable;

class PublishDueScheduledPostsCommand extends Command
{
    protected $signature = 'x:publish-scheduled';

    protected $description = 'Publish any scheduled X posts that are due.';

    public function handle(XApiClient $x): void
    {
        ScheduledPost::with('user')
            ->whereNull('published_at')
            ->whereNull('failed_at')
            ->where('scheduled_at', '<=', now())
            ->each(fn (ScheduledPost $post) => $this->publish($x, $post));
    }

    private function publish(XApiClient $x, ScheduledPost $post): void
    {
        try {
            if ($post->isThread()) {
                $this->publishThread($x, $post);
            } else {
                $this->publishSingle($x, $post);
            }

            $post->update(['published_at' => now()]);
        } catch (XApiException|Throwable $exception) {
            $post->update([
                'failed_at' => now(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function publishSingle(XApiClient $x, ScheduledPost $post): void
    {
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

        $x->forUser($post->user)->post('/tweets', $body);
    }

    private function publishThread(XApiClient $x, ScheduledPost $post): void
    {
        $client = $x->forUser($post->user);
        $replyTo = $post->reply_to_id;

        foreach ($post->thread_posts as $index => $text) {
            $body = ['text' => $text];

            if ($replyTo !== null) {
                $body['reply'] = ['in_reply_to_tweet_id' => $replyTo];
            }

            try {
                $response = $client->post('/tweets', $body);
            } catch (XApiException $exception) {
                throw new XApiException(
                    'Thread stopped at post '.($index + 1).'/'.count($post->thread_posts).': '.$exception->getMessage(),
                    $exception->status,
                    $exception->payload,
                );
            }

            $replyTo = $response['data']['id'] ?? null;
        }
    }
}
