<?php

namespace App\Services;

/**
 * Deterministic keyword + rating based sentiment classifier (EN + AR).
 * Used as the offline fallback when no LLM key is configured, and as a
 * cheap pre-filter before calling the AI engine.
 */
class SentimentClassifier
{
    private const POSITIVE = [
        'excellent', 'amazing', 'great', 'good', 'love', 'perfect', 'friendly', 'clean', 'fast',
        'delicious', 'recommend', 'best', 'awesome', 'fantastic', 'wonderful', 'helpful',
        'ممتاز', 'رائع', 'جميل', 'نظيف', 'سريع', 'لذيذ', 'انصح', 'أنصح', 'افضل', 'أفضل', 'ودود', 'محترم',
    ];

    private const NEGATIVE = [
        'bad', 'terrible', 'awful', 'slow', 'dirty', 'rude', 'worst', 'horrible', 'disappointed',
        'never again', 'expensive', 'cold food', 'waited', 'refund', 'complaint',
        'سيء', 'سيئ', 'بطيء', 'وسخ', 'قذر', 'اسوأ', 'أسوأ', 'غالي', 'مخيب', 'شكوى', 'تأخير', 'لا انصح', 'لا أنصح',
    ];

    public function classify(?string $text, ?float $rating = null): string
    {
        $score = 0;

        if ($text) {
            $lower = mb_strtolower($text);
            foreach (self::POSITIVE as $word) {
                if (str_contains($lower, $word)) {
                    $score++;
                }
            }
            foreach (self::NEGATIVE as $word) {
                if (str_contains($lower, $word)) {
                    $score--;
                }
            }
        }

        if ($rating !== null) {
            $score += match (true) {
                $rating >= 4 => 2,
                $rating >= 3 => 0,
                default => -2,
            };
        }

        return match (true) {
            $score > 0 => 'positive',
            $score < 0 => 'negative',
            default => 'neutral',
        };
    }

    /** Naive topic extraction: most frequent meaningful words. */
    public function extractTopics(string $text, int $limit = 5): array
    {
        $stopWords = ['the', 'and', 'was', 'were', 'this', 'that', 'with', 'have', 'for', 'not', 'very',
            'من', 'في', 'على', 'كان', 'هذا', 'هذه', 'مع', 'جدا', 'جداً', 'انا', 'أنا'];

        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn ($w) => mb_strlen($w) > 3 && ! in_array($w, $stopWords, true));

        return array_slice(array_keys(array_count_values($words)), 0, $limit);
    }
}
