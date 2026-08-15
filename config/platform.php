<?php

use App\Support\Platform\Analytics\DatabaseAnalyticsReader;
use App\Support\Platform\Analytics\DatabaseAnalyticsWriter;
use App\Support\Platform\Analytics\NullAnalyticsEventStore;
use App\Support\Platform\Analytics\UnavailableAnalyticsReader;
use App\Support\Platform\Analytics\UnavailableAnalyticsWriter;
use App\Support\Platform\Authorization\PermissionSlugs;
use App\Support\Platform\Authorization\PlatformPermissionRegistry;
use App\Support\Platform\Authorization\ScopeResolver;
use App\Support\Platform\Authorization\StatamicUserIdentityBridge;
use App\Support\Platform\Domain\DomainResolver;
use App\Support\Platform\Domain\ProductUrlGenerator;
use App\Support\Platform\Domain\RequestContextResolver;

return [

    'analytics' => [
        'driver' => env('ANALYTICS_DRIVER', 'database'),

        'drivers' => [
            'database' => [
                'reader' => DatabaseAnalyticsReader::class,
                'writer' => DatabaseAnalyticsWriter::class,
            ],
            'clickhouse' => [
                'reader' => UnavailableAnalyticsReader::class,
                'writer' => UnavailableAnalyticsWriter::class,
            ],
        ],

        'event_store' => NullAnalyticsEventStore::class,
    ],

    'domain' => [
        'platform_scheme' => env('PLATFORM_SCHEME', 'https'),
        'platform_domain' => env('PLATFORM_DOMAIN', ''),
        'resolver' => DomainResolver::class,
        'url_generator' => ProductUrlGenerator::class,
        'request_context_resolver' => RequestContextResolver::class,
        'default_surface_policy' => 'product_preferred',
        'surface_policies' => [
            'product_required',
            'product_preferred',
            'organisation_fallback',
            'platform_only',
        ],
        'surfaces' => [
            'landing_page' => [
                'policy' => 'product_preferred',
                'product_domain_field' => 'public_domain',
                'path' => '/',
            ],
            'form_page' => [
                'policy' => 'product_preferred',
                'product_domain_field' => 'forms_domain',
                'path' => '/subscribe/{form}',
            ],
            'form_submit_endpoint' => [
                'policy' => 'product_preferred',
                'product_domain_field' => 'forms_domain',
                'path' => '/subscribe/{form}',
            ],
            'preferences_page' => [
                'policy' => 'product_preferred',
                'product_domain_field' => 'public_domain',
                'path' => '/preferences/{subscriber}',
            ],
            'unsubscribe_page' => [
                'policy' => 'product_preferred',
                'product_domain_field' => 'public_domain',
                'path' => '/unsubscribe/{subscriber}',
            ],
            'browser_view_page' => [
                'policy' => 'product_preferred',
                'product_domain_field' => 'public_domain',
                'path' => '/browser-view/{campaign}',
            ],
            'campaign_link' => [
                'policy' => 'product_preferred',
                'product_domain_field' => 'public_domain',
            ],
            'tracking_link' => [
                'policy' => 'product_preferred',
                'product_domain_field' => 'public_domain',
            ],
            'cp' => [
                'policy' => 'platform_only',
            ],
            'webhook' => [
                'policy' => 'platform_only',
            ],
        ],
    ],

    'authorization' => [
        'permission_registry' => PlatformPermissionRegistry::class,
        'scope_resolver' => ScopeResolver::class,
        'statamic_user_identity_bridge' => StatamicUserIdentityBridge::class,

        'permissions' => PermissionSlugs::categories(),

        'scope_statuses' => [
            'active' => 'active',
            'inactive' => 'inactive',
            'revoked' => 'revoked',
        ],
    ],

];
