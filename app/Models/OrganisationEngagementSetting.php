<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganisationEngagementSetting extends Model
{
    protected $fillable = [
        'organisation_id', 'interval_days', 'last_recalculated_at',
        'last_requested_at', 'last_status', 'last_error',
    ];

    protected $casts = [
        'last_recalculated_at' => 'datetime',
        'last_requested_at' => 'datetime',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
