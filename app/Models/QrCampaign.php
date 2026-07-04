<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrCampaign extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(QrFeedback::class);
    }

    public function publicUrl(): string
    {
        return route('feedback.show', $this->slug);
    }

    public function isPositive(int $rating): bool
    {
        return $rating >= $this->threshold;
    }

    public function redirectUrl(): ?string
    {
        return $this->positive_redirect_url ?: $this->branch?->googleMapsUrl();
    }
}
