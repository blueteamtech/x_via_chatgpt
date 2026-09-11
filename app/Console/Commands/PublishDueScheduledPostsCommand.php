<?php

namespace App\Console\Commands;

use App\Jobs\PublishScheduledPost;
use App\Models\ScheduledPost;
use Illuminate\Console\Command;

class PublishDueScheduledPostsCommand extends Command
{
    protected $signature = 'x:publish-scheduled';

    protected $description = 'Dispatch jobs for any scheduled X posts that are due.';

    public function handle(): void
    {
        ScheduledPost::whereNull('published_at')
            ->whereNull('failed_at')
            ->where('scheduled_at', '<=', now())
            ->each(fn (ScheduledPost $post) => PublishScheduledPost::dispatch($post));
    }
}
