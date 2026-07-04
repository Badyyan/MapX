<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'hours' => 'array',
            'images' => 'array',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(PlatformConnection::class);
    }

    public function qrCampaigns(): HasMany
    {
        return $this->hasMany(QrCampaign::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    /** Data completeness score (0-100) used for the presence health score. */
    public function completenessScore(): int
    {
        $fields = ['name', 'description', 'address', 'city', 'lat', 'lng', 'phone', 'website', 'hours', 'categories', 'images'];
        $filled = collect($fields)->filter(fn ($f) => ! empty($this->{$f}))->count();

        return (int) round($filled / count($fields) * 100);
    }

    public function wazeDeepLink(): ?string
    {
        return $this->lat ? "https://waze.com/ul?ll={$this->lat},{$this->lng}&navigate=yes" : null;
    }

    public function uberDeepLink(): ?string
    {
        return $this->lat
            ? "https://m.uber.com/ul/?action=setPickup&dropoff[latitude]={$this->lat}&dropoff[longitude]={$this->lng}&dropoff[nickname]=".urlencode($this->name)
            : null;
    }

    public function careemDeepLink(): ?string
    {
        return $this->lat ? "careem://ride?dropoff_latitude={$this->lat}&dropoff_longitude={$this->lng}" : null;
    }

    public function boltDeepLink(): ?string
    {
        return $this->lat ? "https://bolt.eu/ride?destination_lat={$this->lat}&destination_lng={$this->lng}" : null;
    }

    public function googleMapsUrl(): ?string
    {
        if ($this->google_place_id) {
            return "https://search.google.com/local/writereview?placeid={$this->google_place_id}";
        }

        return $this->lat ? "https://www.google.com/maps/search/?api=1&query={$this->lat},{$this->lng}" : null;
    }
}
