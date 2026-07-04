<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'branch_ids' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'ai_generated' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(PostStatus::class);
    }

    public function branches()
    {
        return Branch::whereIn('id', $this->branch_ids ?? [])->get();
    }
}
