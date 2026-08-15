<?php

namespace App\Models;

use App\Support\Platform\Ownership\HasOwnershipReadScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasOwnershipReadScopes;

    protected $fillable = [
        'organisation_id', 'product_id',
        'name', 'slug', 'description', 'blade_view', 'collection', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
