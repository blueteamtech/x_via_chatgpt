<?php

namespace App\Mcp\Tools;

use App\Models\ScheduledPost;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Schedule a post to be published on X at a future time, or list/cancel pending scheduled posts.')]
class XSchedulePostTool extends XTool
{
    public function handle(Request $request): Response
    {
        $action = $request->validate(['action' => ['required', 'string', 'in:schedule,list,cancel']])['action'];

        return match ($action) {
            'schedule' => $this->schedule($request),
            'list' => $this->list($request),
            'cancel' => $this->cancel($request),
        };
    }

    private function schedule(Request $request): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:4000'],
            'scheduled_at' => ['required', 'string'],
            'reply_to_id' => ['nullable', 'string'],
            'quote_id' => ['nullable', 'string'],
            'media_ids' => ['nullable'],
        ]);

        $scheduledAt = Carbon::parse($validated['scheduled_at'])->utc();

        if ($scheduledAt->isPast()) {
            return Response::error('scheduled_at must be in the future.');
        }

        $user = $request->user();

        $post = ScheduledPost::create([
            'user_id' => $user->getKey(),
            'text' => $validated['text'],
            'reply_to_id' => $validated['reply_to_id'] ?? null,
            'quote_id' => $validated['quote_id'] ?? null,
            'media_ids' => $this->normalizeMediaIds($validated['media_ids'] ?? null),
            'scheduled_at' => $scheduledAt,
        ]);

        // Jobs are dispatched by the scheduler every minute — no delay needed here.

        return Response::json([
            'scheduled_post_id' => $post->id,
            'scheduled_at' => $scheduledAt->toIso8601String(),
            'text' => $post->text,
        ]);
    }

    private function list(Request $request): Response
    {
        $user = $request->user();

        $posts = ScheduledPost::where('user_id', $user->getKey())
            ->whereNull('published_at')
            ->whereNull('failed_at')
            ->orderBy('scheduled_at')
            ->get(['id', 'text', 'scheduled_at', 'reply_to_id', 'quote_id', 'media_ids'])
            ->map(fn (ScheduledPost $post) => [
                'scheduled_post_id' => $post->id,
                'text' => $post->text,
                'scheduled_at' => $post->scheduled_at->toIso8601String(),
                'reply_to_id' => $post->reply_to_id,
                'quote_id' => $post->quote_id,
                'media_ids' => $post->media_ids,
            ]);

        return Response::json(['scheduled_posts' => $posts]);
    }

    private function cancel(Request $request): Response
    {
        $validated = $request->validate([
            'scheduled_post_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $post = ScheduledPost::where('user_id', $user->getKey())
            ->whereNull('published_at')
            ->whereNull('failed_at')
            ->find($validated['scheduled_post_id']);

        if (! $post) {
            return Response::error('Scheduled post not found or already published/cancelled.');
        }

        $post->delete();

        return Response::json(['cancelled' => true, 'scheduled_post_id' => $validated['scheduled_post_id']]);
    }

    /** @return list<string>|null */
    private function normalizeMediaIds(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return array_values((array) $value) ?: null;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description('schedule | list | cancel')->required(),
            'text' => $schema->string()->description('Post text (schedule only).'),
            'scheduled_at' => $schema->string()->description('ISO 8601 datetime in the future (schedule only).'),
            'reply_to_id' => $schema->string()->description('Post id to reply to (schedule only).'),
            'quote_id' => $schema->string()->description('Post id to quote (schedule only).'),
            'media_ids' => $schema->string()->description('Comma-separated media ids from x-upload-media (schedule only).'),
            'scheduled_post_id' => $schema->integer()->description('Id from x-schedule-post to cancel (cancel only).'),
        ];
    }
}
