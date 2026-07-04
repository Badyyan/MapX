<?php

namespace App\Jobs;

use App\Models\ReviewReply;
use App\Services\PlatformManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * FR-14: deliver a reply to the source platform via its API.
 */
class SendReviewReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public ReviewReply $reply)
    {
    }

    public function handle(PlatformManager $platforms): void
    {
        $reply = $this->reply->fresh();

        if (! $reply || $reply->status === 'sent') {
            return;
        }

        $review = $reply->review()->withoutGlobalScope('company')->first();

        $connection = \App\Models\PlatformConnection::withoutGlobalScope('company')
            ->where('company_id', $review->company_id)
            ->where('platform', $review->platform)
            ->where(fn ($q) => $q->where('branch_id', $review->branch_id)->orWhereNull('branch_id'))
            ->first();

        $delivered = $connection
            ? $platforms->adapter($review->platform)->replyToReview($connection, $review, $reply->content)
            : config('mapx.integrations.mock', true); // mock mode succeeds without a connection

        $reply->update([
            'status' => $delivered ? 'sent' : 'failed',
            'sent_at' => $delivered ? now() : null,
        ]);

        if ($delivered) {
            $review->update(['is_replied' => true]);
        }
    }
}
