<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'text',
    'thread_posts',
    'reply_to_id',
    'quote_id',
    'media_ids',
    'scheduled_at',
    'published_at',
    'failed_at',
    'error',
])]
class ScheduledPost extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'media_ids' => 'array',
            'thread_posts' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function isThread(): bool
    {
        return is_array($this->thread_posts) && count($this->thread_posts) > 0;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->published_at === null && $this->failed_at === null;
    }
}
