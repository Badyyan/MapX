<?php

namespace App\Services;

use App\Jobs\SendReviewReply;
use App\Models\AutoReplyRule;
use App\Models\Review;
use App\Models\ReviewReply;

/**
 * FR-16/FR-17: apply the highest-priority matching auto-reply rule to an
 * incoming review. Replies either go to a pending-approval queue or are
 * dispatched to the platform after the configured delay.
 */
class AutoReplyEngine
{
    public function process(Review $review): ?ReviewReply
    {
        if ($review->is_replied || $review->replies()->exists()) {
            return null;
        }

        $rule = AutoReplyRule::withoutGlobalScope('company')
            ->where('company_id', $review->company_id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get()
            ->first(fn (AutoReplyRule $rule) => $rule->matches($review));

        if (! $rule || ! ($template = $rule->pickTemplate())) {
            return null;
        }

        $reply = ReviewReply::create([
            'review_id' => $review->id,
            'auto_reply_rule_id' => $rule->id,
            'content' => $this->render($template, $review),
            'source' => 'auto_rule',
            'status' => $rule->require_approval ? 'pending_approval' : 'queued',
        ]);

        if (! $rule->require_approval) {
            SendReviewReply::dispatch($reply)->delay(now()->addMinutes($rule->delay_minutes));
        }

        return $reply;
    }

    /** Replace {name}, {rating}, {branch} placeholders in templates. */
    private function render(string $template, Review $review): string
    {
        return strtr($template, [
            '{name}' => $review->author_name ?? __('valued customer'),
            '{rating}' => (string) $review->rating,
            '{branch}' => $review->branch?->name ?? '',
        ]);
    }
}
