<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $guarded = [];

    /** The saved card token must never reach a response, log or audit entry. */
    protected $hidden = ['payment_token'];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'canceled_at' => 'datetime',
            'last_renewal_attempt_at' => 'datetime',
            'price_per_branch' => 'float',
            // Encrypted at rest, like PlatformConnection's OAuth tokens.
            'payment_token' => 'encrypted',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function onTrial(): bool
    {
        return $this->status === 'trialing' && $this->trial_ends_at?->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' || $this->onTrial();
    }

    public function isLocked(): bool
    {
        return ! $this->isActive();
    }
}
