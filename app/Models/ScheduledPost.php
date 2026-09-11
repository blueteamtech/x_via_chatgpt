<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'text',
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
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
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
