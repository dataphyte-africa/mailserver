<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'organisation_id',
        'name',
        'slug',
        'status',
        'product_type',
        'public_domain',
        'mail_from_domain',
        'forms_domain',
        'domain_status',
        'domain_verified_at',
        'domain_is_primary',
        'primary_collection_handle',
        'default_sender_profile',
        'default_template_family',
        'fallback_to_platform_domain',
    ];

    protected $casts = [
        'domain_verified_at' => 'datetime',
        'domain_is_primary' => 'boolean',
        'default_sender_profile' => 'array',
        'fallback_to_platform_domain' => 'boolean',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function userScopes(): HasMany
    {
        return $this->hasMany(ProductUserScope::class);
    }

    public function subscriberGroups(): HasMany
    {
        return $this->hasMany(SubscriberGroup::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }
}
