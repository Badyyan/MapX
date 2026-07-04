<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrFeedback extends Model
{
    use BelongsToCompany;

    protected $table = 'qr_feedback';

    protected $guarded = [];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(QrCampaign::class, 'qr_campaign_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
