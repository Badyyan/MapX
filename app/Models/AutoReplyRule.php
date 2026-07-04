<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AutoReplyRule extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'require_approval' => 'boolean',
            'keywords' => 'array',
            'platforms' => 'array',
            'templates' => 'array',
        ];
    }

    /** Whether this rule matches a given review. */
    public function matches(Review $review): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($review->rating !== null && ($review->rating < $this->min_rating || $review->rating > $this->max_rating)) {
            return false;
        }
        if ($this->sentiment && $review->sentiment !== $this->sentiment) {
            return false;
        }
        if (! empty($this->platforms) && ! in_array($review->platform, $this->platforms, true)) {
            return false;
        }
        if ($this->language && $review->language && $review->language !== $this->language) {
            return false;
        }
        if (! empty($this->keywords)) {
            $content = mb_strtolower($review->content ?? '');
            $hit = collect($this->keywords)->contains(fn ($kw) => $kw !== '' && str_contains($content, mb_strtolower($kw)));
            if (! $hit) {
                return false;
            }
        }

        return true;
    }

    public function pickTemplate(): ?string
    {
        $templates = array_values(array_filter($this->templates ?? []));

        return $templates ? $templates[array_rand($templates)] : null;
    }
}
