<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Record an audit entry for the current user. */
    public static function record(string $action, ?Model $subject = null, array $meta = []): self
    {
        return static::create([
            'company_id' => auth()->user()?->company_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
        ]);
    }

    /**
     * Record an entry from a context with no authenticated user — webhooks
     * and queued jobs. The company has to be passed explicitly because
     * there's no session to infer it from.
     */
    public static function recordSystem(string $action, int $companyId, ?Model $subject = null, array $meta = []): self
    {
        return static::create([
            'company_id' => $companyId,
            'user_id' => null,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
