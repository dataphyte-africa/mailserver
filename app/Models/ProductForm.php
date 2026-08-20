<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'organisation_id',
        'product_id',
        'form_scope',
        'product_selection_field',
        'allowed_product_ids',
        'name',
        'slug',
        'mode',
        'template_family',
        'status',
        'headline',
        'description',
        'success_message',
        'field_definitions',
        'allowed_origins',
        'settings',
        'requires_review',
        'audience_group_id',
        'audience_sub_group_id',
        'custom_extension_key',
    ];

    protected $casts = [
        'field_definitions' => 'array',
        'allowed_origins' => 'array',
        'allowed_product_ids' => 'array',
        'settings' => 'array',
        'requires_review' => 'boolean',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function audienceGroup(): BelongsTo
    {
        return $this->belongsTo(SubscriberGroup::class, 'audience_group_id');
    }

    public function audienceSubGroup(): BelongsTo
    {
        return $this->belongsTo(SubscriberSubGroup::class, 'audience_sub_group_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ProductFormSubmission::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
