<?php

namespace App\Mcp\Tools;

use App\Exceptions\XApiException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Post a thread on X. Accepts either a pre-split array of post texts, or a single long text that will be auto-split at ~275 char boundaries. Each post replies to the previous one.')]
class XCreateThreadTool extends XTool
{
    /**
     * Leave headroom under the free-tier 280 character limit so a rare emoji
     * expansion (X counts some multi-byte glyphs as 2) doesn't push us over.
     */
    private const AUTO_SPLIT_LIMIT = 275;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'posts' => ['nullable', 'array'],
            'posts.*' => ['string', 'max:4000'],
            'reply_to_id' => ['nullable', 'string'],
        ]);

        if (empty($validated['text']) && empty($validated['posts'])) {
            return Response::error('Pass either a text (to auto-split) or a posts array.');
        }

        $posts = $validated['posts'] ?? $this->splitIntoPosts($validated['text']);

        if ($posts === []) {
            return Response::error('Nothing to post.');
        }

        return $this->respond($request, function ($x) use ($posts, $validated) {
            $created = [];
            $replyTo = $validated['reply_to_id'] ?? null;

            foreach ($posts as $index => $text) {
                $body = ['text' => $text];

                if ($replyTo !== null) {
                    $body['reply'] = ['in_reply_to_tweet_id' => $replyTo];
                }

                try {
                    $response = $x->post('/tweets', $body);
                } catch (XApiException $exception) {
                    throw new XApiException(
                        'Thread stopped at post '.($index + 1).'/'.count($posts).': '.$exception->getMessage(),
                        $exception->status,
                        $exception->payload,
                    );
                }

                $id = $response['data']['id'] ?? null;
                $created[] = ['position' => $index + 1, 'id' => $id, 'text' => $text];
                $replyTo = $id;
            }

            return [
                'thread_length' => count($created),
                'root_post_id' => $created[0]['id'] ?? null,
                'posts' => $created,
            ];
        });
    }

    /**
     * Split a long text into posts under the character limit.
     *
     * Splits on double newlines first (paragraph breaks), then falls back to
     * sentence boundaries, then to word boundaries. Never breaks mid-word.
     *
     * @return list<string>
     */
    private function splitIntoPosts(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= self::AUTO_SPLIT_LIMIT) {
            return [$text];
        }

        $paragraphs = preg_split('/\n\s*\n/', $text) ?: [$text];
        $posts = [];
        $buffer = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($paragraph) > self::AUTO_SPLIT_LIMIT) {
                if ($buffer !== '') {
                    $posts[] = $buffer;
                    $buffer = '';
                }

                foreach ($this->splitOversizedParagraph($paragraph) as $chunk) {
                    $posts[] = $chunk;
                }

                continue;
            }

            $candidate = $buffer === '' ? $paragraph : $buffer."\n\n".$paragraph;

            if (mb_strlen($candidate) <= self::AUTO_SPLIT_LIMIT) {
                $buffer = $candidate;
            } else {
                $posts[] = $buffer;
                $buffer = $paragraph;
            }
        }

        if ($buffer !== '') {
            $posts[] = $buffer;
        }

        return $posts;
    }

    /**
     * Break one paragraph that's already longer than the limit into
     * sentence-then-word chunks that fit.
     *
     * @return list<string>
     */
    private function splitOversizedParagraph(string $paragraph): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph) ?: [$paragraph];
        $chunks = [];
        $buffer = '';

        foreach ($sentences as $sentence) {
            $candidate = $buffer === '' ? $sentence : $buffer.' '.$sentence;

            if (mb_strlen($candidate) <= self::AUTO_SPLIT_LIMIT) {
                $buffer = $candidate;

                continue;
            }

            if ($buffer !== '') {
                $chunks[] = $buffer;
                $buffer = '';
            }

            if (mb_strlen($sentence) <= self::AUTO_SPLIT_LIMIT) {
                $buffer = $sentence;

                continue;
            }

            foreach ($this->wordWrap($sentence) as $piece) {
                $chunks[] = $piece;
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks;
    }

    /**
     * Last resort: hard-wrap on word boundaries when a single sentence
     * exceeds the limit.
     *
     * @return list<string>
     */
    private function wordWrap(string $sentence): array
    {
        $words = preg_split('/\s+/', $sentence) ?: [];
        $chunks = [];
        $buffer = '';

        foreach ($words as $word) {
            $candidate = $buffer === '' ? $word : $buffer.' '.$word;

            if (mb_strlen($candidate) <= self::AUTO_SPLIT_LIMIT) {
                $buffer = $candidate;
            } else {
                if ($buffer !== '') {
                    $chunks[] = $buffer;
                }

                $buffer = $word;
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->description('One long text to auto-split into a thread. Use this OR posts.'),
            'posts' => $schema->array()->description('Pre-split array of post texts, in order. Use this OR text.'),
            'reply_to_id' => $schema->string()->description('Optional: make the whole thread a reply to this post id.'),
        ];
    }
}
