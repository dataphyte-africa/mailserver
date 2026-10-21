<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriberGroupEngagement extends Model
{
    protected $fillable = [
        'subscriber_id', 'subscriber_group_id', 'rating', 'score', 'is_member',
        'first_joined_at', 'last_engaged_at', 'last_recalculated_at',
    ];

    protected $casts = [
        'first_joined_at' => 'datetime',
        'is_member' => 'boolean',
        'last_engaged_at' => 'datetime',
        'last_recalculated_at' => 'datetime',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SubscriberGroup::class, 'subscriber_group_id');
    }
}
