<?php

namespace App\Services;

use App\Models\Review;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI engine (SRS 1.3): review reply suggestions, post drafts and sentiment.
 * Talks to any OpenAI-compatible API when configured; otherwise falls back
 * to a deterministic offline generator so the platform works without keys.
 */
class AiService
{
    public function __construct(private SentimentClassifier $classifier)
    {
    }

    public function isOnline(): bool
    {
        return config('mapx.ai.provider') !== 'offline' && config('mapx.ai.api_key');
    }

    /** FR-15: generate a reply suggestion for a review. */
    public function suggestReviewReply(Review $review, string $tone = 'professional'): string
    {
        $locale = $review->language === 'ar' ? 'ar' : 'en';

        if ($this->isOnline()) {
            $prompt = "You are a customer-care specialist for \"{$review->branch?->name}\". "
                ."Write a short, {$tone} reply in ".($locale === 'ar' ? 'Arabic' : 'English')
                ." to this {$review->rating}-star review. Do not invent facts or offer compensation.\n\n"
                ."Review by {$review->author_name}: {$review->content}";

            if ($reply = $this->complete($prompt)) {
                return $reply;
            }
        }

        return $this->offlineReply($review, $locale);
    }

    /** FR-22: generate a post draft. */
    public function generatePostDraft(string $topic, string $locale = 'en', string $businessName = ''): string
    {
        if ($this->isOnline()) {
            $prompt = 'Write a short engaging social/business-listing post in '
                .($locale === 'ar' ? 'Arabic' : 'English')
                ." for the business \"{$businessName}\" about: {$topic}. "
                .'Max 80 words, include a call to action, no hashtags spam (max 3).';

            if ($draft = $this->complete($prompt)) {
                return $draft;
            }
        }

        return $this->offlinePostDraft($topic, $locale, $businessName);
    }

    /** FR-13: classify review sentiment. */
    public function classifySentiment(?string $text, ?float $rating = null): string
    {
        return $this->classifier->classify($text, $rating);
    }

    public function extractTopics(?string $text): array
    {
        return $text ? $this->classifier->extractTopics($text) : [];
    }

    private function complete(string $prompt): ?string
    {
        try {
            $response = Http::withToken(config('mapx.ai.api_key'))
                ->timeout(30)
                ->post(rtrim(config('mapx.ai.base_url'), '/').'/chat/completions', [
                    'model' => config('mapx.ai.model'),
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 300,
                ]);

            return $response->successful()
                ? trim($response->json('choices.0.message.content', ''))
                : null;
        } catch (\Throwable $e) {
            Log::warning('AI completion failed, falling back offline', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function offlineReply(Review $review, string $locale): string
    {
        $name = $review->author_name ?: ($locale === 'ar' ? 'عميلنا العزيز' : 'valued customer');
        $sentiment = $review->sentiment !== 'unknown'
            ? $review->sentiment
            : $this->classifySentiment($review->content, $review->rating);

        if ($locale === 'ar') {
            return match ($sentiment) {
                'positive' => "شكراً جزيلاً {$name} على تقييمك الرائع! يسعدنا أن تجربتك كانت مميزة، ونتطلع لخدمتك مرة أخرى قريباً.",
                'negative' => "نعتذر بصدق {$name} عن التجربة التي مررت بها. نأخذ ملاحظاتك على محمل الجد وسيتواصل معك فريقنا لمعالجة الأمر. نتمنى أن نستعيد ثقتك قريباً.",
                default => "شكراً {$name} على مشاركة تجربتك. ملاحظاتك تساعدنا على التحسن باستمرار، ونتطلع لرؤيتك مجدداً.",
            };
        }

        return match ($sentiment) {
            'positive' => "Thank you so much, {$name}! We're delighted you had a great experience and we look forward to welcoming you back soon.",
            'negative' => "We sincerely apologize, {$name}. Your feedback is important to us and our team will look into this right away. We hope to earn back your trust.",
            default => "Thank you for your feedback, {$name}. It helps us improve, and we hope to see you again soon.",
        };
    }

    private function offlinePostDraft(string $topic, string $locale, string $businessName): string
    {
        if ($locale === 'ar') {
            return "📢 {$businessName}: {$topic}\n\nيسعدنا دائماً تقديم الأفضل لعملائنا الكرام. زورونا اليوم واكتشفوا الجديد بأنفسكم!\n\n📍 تفضلوا بزيارتنا أو تواصلوا معنا لمعرفة المزيد.";
        }

        return "📢 {$businessName}: {$topic}\n\nWe're always working to bring you the best experience. Visit us today and see what's new!\n\n📍 Drop by or get in touch to learn more.";
    }
}
