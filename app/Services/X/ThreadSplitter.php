<?php

namespace App\Services\X;

class ThreadSplitter
{
    /**
     * Leave headroom under the free-tier 280 char limit so a rare emoji
     * expansion (X counts some multi-byte glyphs as 2) doesn't push us over.
     */
    public const LIMIT = 275;

    /**
     * Split a long text into posts that each fit under the limit.
     *
     * Splits on paragraph breaks first, then sentence boundaries, then word
     * boundaries. Never breaks mid-word.
     *
     * @return list<string>
     */
    public function split(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= self::LIMIT) {
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

            if (mb_strlen($paragraph) > self::LIMIT) {
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

            if (mb_strlen($candidate) <= self::LIMIT) {
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
     * @return list<string>
     */
    private function splitOversizedParagraph(string $paragraph): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph) ?: [$paragraph];
        $chunks = [];
        $buffer = '';

        foreach ($sentences as $sentence) {
            $candidate = $buffer === '' ? $sentence : $buffer.' '.$sentence;

            if (mb_strlen($candidate) <= self::LIMIT) {
                $buffer = $candidate;

                continue;
            }

            if ($buffer !== '') {
                $chunks[] = $buffer;
                $buffer = '';
            }

            if (mb_strlen($sentence) <= self::LIMIT) {
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
     * @return list<string>
     */
    private function wordWrap(string $sentence): array
    {
        $words = preg_split('/\s+/', $sentence) ?: [];
        $chunks = [];
        $buffer = '';

        foreach ($words as $word) {
            $candidate = $buffer === '' ? $word : $buffer.' '.$word;

            if (mb_strlen($candidate) <= self::LIMIT) {
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
}
