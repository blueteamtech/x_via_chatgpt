<?php

namespace App\Services\X;

use App\Exceptions\XApiException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DmGuard
{
    /**
     * Verify the user is allowed to send this DM. Throws if blocked by
     * daily volume cap or identical-content anti-spam rule.
     *
     * @throws XApiException
     */
    public function ensureCanSend(User $user, string $recipientId, string $text): void
    {
        $dailyCap = (int) config('credits.dm_daily_max', 20);
        $identicalCap = (int) config('credits.dm_identical_content_max_recipients', 3);
        $contentHash = hash('sha256', trim($text));
        $userKey = $user->getKey();

        $sentToday = DB::table('dm_send_log')
            ->where('user_id', $userKey)
            ->where('sent_at', '>=', now()->startOfDay())
            ->count();

        if ($sentToday >= $dailyCap) {
            throw new XApiException(
                "Daily DM limit reached ({$dailyCap}/day). X's own anti-spam limits kick in around this level. Try again tomorrow."
            );
        }

        $identicalRecipients = DB::table('dm_send_log')
            ->where('user_id', $userKey)
            ->where('content_hash', $contentHash)
            ->where('sent_at', '>=', now()->subDay())
            ->distinct()
            ->count('recipient_id');

        if ($identicalRecipients >= $identicalCap) {
            throw new XApiException(
                'Blocked: this exact message has already been sent to '.$identicalRecipients.' different recipients in the last 24 hours. Sending identical DMs to multiple recipients looks like spam and can get your X account (and our whole app) suspended. Vary the message per recipient.'
            );
        }
    }

    /**
     * Record a successfully sent DM for future cap/spam checks.
     */
    public function record(User $user, string $recipientId, string $text): void
    {
        DB::table('dm_send_log')->insert([
            'user_id' => $user->getKey(),
            'recipient_id' => $recipientId,
            'content_hash' => hash('sha256', trim($text)),
            'sent_at' => now(),
        ]);
    }
}
