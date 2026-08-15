<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatamicGroupScopeMap extends Model
{
    use HasFactory;

    protected $table = 'statamic_group_scope_map';

    protected $fillable = [
        'group_handle',
        'scope_type',
        'organisation_id',
        'product_id',
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
