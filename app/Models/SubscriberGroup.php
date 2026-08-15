<?php

namespace App\Models;

use App\Support\Platform\Ownership\HasOwnershipReadScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriberGroup extends Model
{
    use HasFactory, HasOwnershipReadScopes;

    protected $fillable = [
        'organisation_id',
        'product_id',
        'name',
        'slug',
        'collection_handle',
        'description',
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subGroups(): HasMany
    {
        return $this->hasMany(SubscriberSubGroup::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function activeSubscribersCount(): int
    {
        return $this->subGroups()
            ->withCount(['subscribers' => fn ($q) => $q->where('subscribers.status', 'active')])
            ->get()
            ->sum('subscribers_count');
    }
}
