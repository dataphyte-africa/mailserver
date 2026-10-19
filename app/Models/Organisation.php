<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'primary_collection_handle',
        'default_domain',
        'source_domain',
        'newsletter_subdomain',
        'newsletter_domain',
        'newsletter_domain_status',
        'newsletter_domain_verified_at',
        'newsletter_dns_record_type',
        'newsletter_dns_expected_value',
        'default_mail_domain',
        'default_from_name',
        'default_reply_to',
        'compliance_profile',
        'support_contact',
        'postal_address',
        'addresses',
        'website_url',
        'social_links',
        'logo_path',
        'banner_path',
        'brand_primary_color',
        'brand_secondary_color',
        'unsubscribe_message',
    ];

    protected $casts = [
        'compliance_profile' => 'array',
        'social_links' => 'array',
        'addresses' => 'array',
        'newsletter_domain_verified_at' => 'datetime',
    ];

    public function publicAddresses(): array
    {
        if ($this->addresses !== null) {
            return $this->addresses;
        }

        return $this->postal_address
            ? [['label' => 'Main office', 'address' => $this->postal_address]]
            : [];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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

    public function userScopes(): HasMany
    {
        return $this->hasMany(OrganisationUserScope::class);
    }
}
