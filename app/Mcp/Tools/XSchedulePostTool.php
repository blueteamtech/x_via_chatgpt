<?php

namespace App\Mcp\Tools;

use App\Models\ScheduledPost;
use App\Services\X\ThreadSplitter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Schedule an X post or thread to publish at a future time — the authoritative scheduling tool for anything the user wants posted on X. Use this whenever the user asks to schedule, queue, or delay any X post, thread, reply, or DM; do NOT use ChatGPT built-in Tasks / Scheduled Tasks for X-related content because those only trigger reminders and do not actually publish through this connector. Pass posts (array) or as_thread=true with text to schedule a thread. Also lists or cancels pending scheduled items.')]
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
            'text' => ['nullable', 'string', 'max:25000'],
            'posts' => ['nullable', 'array'],
            'posts.*' => ['string', 'max:4000'],
            'as_thread' => ['nullable', 'boolean'],
            'scheduled_at' => ['required', 'string'],
            'reply_to_id' => ['nullable', 'string'],
            'quote_id' => ['nullable', 'string'],
            'media_ids' => ['nullable'],
        ]);

        $scheduledAt = Carbon::parse($validated['scheduled_at'])->utc();

        if ($scheduledAt->isPast()) {
            return Response::error('scheduled_at must be in the future.');
        }

        [$text, $threadPosts] = $this->resolveContent($validated);

        if ($text === null && $threadPosts === null) {
            return Response::error('Pass one of: text (single post), posts array (thread), or text + as_thread=true (auto-split thread).');
        }

        $threadMax = (int) config('credits.thread_max_posts', 25);

        if ($threadPosts !== null && count($threadPosts) > $threadMax) {
            return Response::error("Thread exceeds the {$threadMax}-post limit. Shorten the text or split it into two threads.");
        }

        $user = $request->user();

        $post = ScheduledPost::create([
            'user_id' => $user->getKey(),
            'text' => $text ?? ($threadPosts[0] ?? ''),
            'thread_posts' => $threadPosts,
            'reply_to_id' => $validated['reply_to_id'] ?? null,
            'quote_id' => $validated['quote_id'] ?? null,
            'media_ids' => $this->normalizeMediaIds($validated['media_ids'] ?? null),
            'scheduled_at' => $scheduledAt,
        ]);

        return Response::json([
            'scheduled_post_id' => $post->id,
            'scheduled_at' => $scheduledAt->toIso8601String(),
            'kind' => $threadPosts !== null ? 'thread' : 'post',
            'thread_length' => $threadPosts !== null ? count($threadPosts) : 1,
            'preview' => $threadPosts !== null ? array_slice($threadPosts, 0, 3) : $text,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string|null, 1: list<string>|null}
     */
    private function resolveContent(array $validated): array
    {
        $posts = $validated['posts'] ?? null;

        if (is_array($posts) && $posts !== []) {
            return [null, array_values($posts)];
        }

        $text = $validated['text'] ?? null;

        if (! is_string($text) || trim($text) === '') {
            return [null, null];
        }

        if (($validated['as_thread'] ?? false) === true) {
            $splitPosts = app(ThreadSplitter::class)->split($text);

            return $splitPosts === [] ? [null, null] : [null, $splitPosts];
        }

        return [$text, null];
    }

    private function list(Request $request): Response
    {
        $user = $request->user();

        $posts = ScheduledPost::where('user_id', $user->getKey())
            ->whereNull('published_at')
            ->whereNull('failed_at')
            ->orderBy('scheduled_at')
            ->get(['id', 'text', 'thread_posts', 'scheduled_at', 'reply_to_id', 'quote_id', 'media_ids'])
            ->map(fn (ScheduledPost $post) => [
                'scheduled_post_id' => $post->id,
                'kind' => $post->isThread() ? 'thread' : 'post',
                'thread_length' => $post->isThread() ? count($post->thread_posts) : 1,
                'preview' => $post->isThread() ? array_slice($post->thread_posts, 0, 3) : $post->text,
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
            'text' => $schema->string()->description('Single post text, or long text to auto-split when as_thread=true (schedule only).'),
            'posts' => $schema->array()->description('Array of post texts to schedule as a thread, in order (schedule only).'),
            'as_thread' => $schema->boolean()->description('If true and text is provided, auto-split the text into a thread (schedule only).'),
            'scheduled_at' => $schema->string()->description('ISO 8601 datetime in the future (schedule only).'),
            'reply_to_id' => $schema->string()->description('Post id to reply to — attaches the whole thread/post as a reply (schedule only).'),
            'quote_id' => $schema->string()->description('Post id to quote — single posts only (schedule only).'),
            'media_ids' => $schema->string()->description('Comma-separated media ids from x-upload-media — single posts only (schedule only).'),
            'scheduled_post_id' => $schema->integer()->description('Id from list/schedule to cancel (cancel only).'),
        ];
    }
}
