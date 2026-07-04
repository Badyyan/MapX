<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\Post;
use App\Models\PostStatus;
use App\Services\PlatformManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * FR-21/FR-23/FR-24: publish a post to every selected platform for every
 * selected branch, recording a per-platform-per-branch status row.
 */
class PublishPost implements ShouldQueue
{
    use Queueable;

    public function __construct(public Post $post)
    {
    }

    public function handle(PlatformManager $platforms): void
    {
        $post = $this->post->fresh();

        if (! $post || in_array($post->status, ['published', 'canceled'], true)) {
            return;
        }

        $post->update(['status' => 'publishing']);

        $branches = Branch::withoutGlobalScope('company')
            ->whereIn('id', $post->branch_ids ?? [])->get();

        $anyFailed = false;
        $anyPublished = false;

        foreach ($branches as $branch) {
            foreach ($post->platforms as $platform) {
                $status = PostStatus::firstOrCreate(
                    ['post_id' => $post->id, 'branch_id' => $branch->id, 'platform' => $platform],
                    ['status' => 'pending'],
                );

                if ($status->status === 'published') {
                    continue;
                }

                $connection = PlatformConnection::withoutGlobalScope('company')
                    ->where('company_id', $post->company_id)
                    ->where('platform', $platform)
                    ->where(fn ($q) => $q->where('branch_id', $branch->id)->orWhereNull('branch_id'))
                    ->first();

                if (! $connection && ! config('mapx.integrations.mock', true)) {
                    $status->update(['status' => 'failed', 'error' => 'Platform not connected']);
                    $anyFailed = true;

                    continue;
                }

                try {
                    $result = $platforms->adapter($platform)->publishPost(
                        $connection ?? new PlatformConnection(['platform' => $platform]),
                        $post,
                        $branch,
                    );

                    $status->update([
                        'status' => $result['status'],
                        'external_id' => $result['external_id'],
                        'error' => $result['error'],
                        'published_at' => $result['status'] === 'published' ? now() : null,
                    ]);

                    $result['status'] === 'published' ? $anyPublished = true : $anyFailed = true;
                } catch (\Throwable $e) {
                    $status->update(['status' => 'failed', 'error' => $e->getMessage()]);
                    $anyFailed = true;
                }
            }
        }

        $post->update([
            'status' => match (true) {
                $anyPublished && $anyFailed => 'partial',
                $anyPublished => 'published',
                default => 'failed',
            },
            'published_at' => $anyPublished ? now() : null,
        ]);
    }
}
