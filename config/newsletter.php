<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Physical Address (CAN-SPAM required)
    |--------------------------------------------------------------------------
    | Appears in the footer of every newsletter email.
    */

    'physical_address' => env('NEWSLETTER_PHYSICAL_ADDRESS', ''),

    /*
    |--------------------------------------------------------------------------
    | Collection Senders And Footer Data
    |--------------------------------------------------------------------------
    | Maps each Statamic collection handle to its sender identity and the
    | shared footer content reused across all blueprint templates under that
    | collection.
    */

    'collections' => [

        'insight_newsletters' => [
            'label'       => 'Dataphyte Insight',
            'short_label' => 'Insight',
            'group_name'  => 'Insight Subscribers',
            'group_slug'  => 'insight-subscribers',
            'from_email'  => env('NEWSLETTER_INSIGHT_FROM_EMAIL', 'newsletter@dataphyte.com'),
            'from_name'   => env('NEWSLETTER_INSIGHT_FROM_NAME', 'Dataphyte Insight'),
            'reply_to'    => env('NEWSLETTER_INSIGHT_REPLY_TO', ''),
            'brand_color' => '#0d1b2a',
            'footer'      => [
                'social_links' => [
                    'facebook' => '#',
                    'twitter' => '#',
                    'linkedin' => '#',
                    'whatsapp' => '#',
                    'youtube' => '#',
                    'instagram' => '#',
                    'tiktok' => '#',
                ],
                'offices' => [
                    [
                        'label' => 'Dataphyte, Nigeria',
                        'address' => 'Plot 404, Marcus Garvey Street, 5th Avenue, Gwarimpa, Abuja, Nigeria.',
                    ],
                    [
                        'label' => 'Dataphyte, United State',
                        'address' => '1007 N Orange St. 4th Floor, Wilmington, Delaware, United States.',
                    ],
                    [
                        'label' => 'Dataphyte, United Kingdom',
                        'address' => 'Chalice House Bromley Road, Elmstead, Colchester, England, CO7 7BY.',
                    ],
                ],
            ],
        ],

        'foundation_newsletters' => [
            'label'       => 'Dataphyte Foundation',
            'short_label' => 'Foundation',
            'group_name'  => 'Foundation',
            'group_slug'  => 'foundation',
            'from_email'  => env('NEWSLETTER_FOUNDATION_FROM_EMAIL', 'newsletter@dataphyte.org'),
            'from_name'   => env('NEWSLETTER_FOUNDATION_FROM_NAME', 'Dataphyte Foundation'),
            'reply_to'    => env('NEWSLETTER_FOUNDATION_REPLY_TO', ''),
            'brand_color' => '#1b4332',
            'footer'      => [
                'social_links' => [
                    'facebook' => '#',
                    'twitter' => '#',
                    'linkedin' => '#',
                    'whatsapp' => '#',
                    'youtube' => '#',
                    'instagram' => '#',
                    'tiktok' => '#',
                ],
                'offices' => [
                    [
                        'label' => 'Dataphyte, Nigeria',
                        'address' => 'Plot 404, Marcus Garvey Street, 5th Avenue, Gwarimpa, Abuja, Nigeria.',
                    ],
                    [
                        'label' => 'Dataphyte, United State',
                        'address' => '1007 N Orange St. 4th Floor, Wilmington, Delaware, United States.',
                    ],
                    [
                        'label' => 'Dataphyte, United Kingdom',
                        'address' => 'Chalice House Bromley Road, Elmstead, Colchester, England, CO7 7BY.',
                    ],
                ],
            ],
        ],

        'policy_point_newsletters' => [
            'label'       => 'Policy Point',
            'short_label' => 'Policy Point',
            'group_name'  => 'Policy Point',
            'group_slug'  => 'policy-point',
            'from_email'  => env('NEWSLETTER_POLICY_POINT_FROM_EMAIL', 'newsletter@dataphyte.com'),
            'from_name'   => env('NEWSLETTER_POLICY_POINT_FROM_NAME', 'Policy Point'),
            'reply_to'    => env('NEWSLETTER_POLICY_POINT_REPLY_TO', ''),
            'brand_color' => '#3d405b',
            'footer'      => [
                'social_links' => [
                    'facebook' => 'https://web.facebook.com/dataphyte',
                    'twitter' => 'https://x.com/Dataphyte',
                    'whatsapp' => 'https://whatsapp.com/channel/0029VaF9xCo6hENiEXLcFS3Q',
                    'youtube' => 'https://www.youtube.com/@dataphyte',
                    'instagram' => 'https://www.instagram.com/dataphyteng',
                    'tiktok' => 'https://www.tiktok.com/@dataphyte',
                    'linkedin' => 'https://www.linkedin.com/in/dataphyte',
                ],
                'offices' => [
                    [
                        'label' => 'Dataphyte, Nigeria',
                        'address' => 'Plot 404, Marcus Garvey Street, 5th Avenue, Gwarimpa, Abuja, Nigeria.',
                    ],
                    [
                        'label' => 'Dataphyte, United State',
                        'address' => '1007 N Orange St. 4th Floor, Wilmington, Delaware, United States.',
                    ],
                    [
                        'label' => 'Dataphyte, United Kingdom',
                        'address' => 'Chalice House Bromley Road, Elmstead, Colchester, England, CO7 7BY.',
                    ],
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Sender
    |--------------------------------------------------------------------------
    | Used when a campaign's collection does not match any entry above.
    */

    'fallback' => [
        'from_email' => env('MAIL_FROM_ADDRESS', 'newsletter@dataphyte.com'),
        'from_name'  => env('MAIL_FROM_NAME', 'Dataphyte'),
        'reply_to'   => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    | Optional shared secret appended to the Elastic Email webhook URL:
    |   https://yourdomain.com/webhooks/elastic-email?secret=YOUR_SECRET
    |
    | Configure in Elastic Email dashboard under Settings > Notifications.
    | Leave blank to skip verification (not recommended in production).
    */

    'webhook_secret' => env('ELASTIC_EMAIL_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Email Send Rate (per minute)
    |--------------------------------------------------------------------------
    | Controls how many emails the queue worker sends per minute.
    | Set this based on your Elastic Email plan's daily sending limit.
    |
    | Quick reference (assumes a 16-hour sending window per day):
    |   100/day    →   1
    |   1,000/day  →   1
    |   5,000/day  →   5
    |   10,000/day →  10
    |   50,000/day →  52
    |  100,000/day → 104
    |
    | Default: 50 — conservative starting point. Increase once you confirm
    | your Elastic Email plan limit.
    */

    'send_rate' => (int) env('ELASTIC_EMAIL_SEND_RATE', 50),

    /*
    |--------------------------------------------------------------------------
    | Analytics Sync — Job 1: Recent (runs hourly)
    |--------------------------------------------------------------------------
    | Scans sends from the last N hours. Catches deliveries, opens, and clicks
    | shortly after a campaign is sent. Keep the limit modest — this runs every
    | hour so unchecked sends roll into the next run automatically.
    |
    |  SYNC_RECENT_HOURS  — how far back to look (default: 8 hours)
    |  SYNC_RECENT_LIMIT  — max sends to check per run (default: 500)
    */

    'sync_recent_hours' => (int) env('SYNC_RECENT_HOURS', 8),
    'sync_recent_limit' => (int) env('SYNC_RECENT_LIMIT', 500),

    /*
    |--------------------------------------------------------------------------
    | Analytics Sync — Job 2: Deep Scan (runs daily at 2 AM)
    |--------------------------------------------------------------------------
    | Scans all unresolved sends from the last N days. Catches late opens and
    | clicks from subscribers who engage days after the campaign was sent.
    | Runs off-peak so a higher limit is safe.
    |
    |  SYNC_DEEP_DAYS   — how far back to look (default: 30 days)
    |  SYNC_DEEP_LIMIT  — max sends to check per run (default: 2000)
    */

    'sync_deep_days'  => (int) env('SYNC_DEEP_DAYS',  30),
    'sync_deep_limit' => (int) env('SYNC_DEEP_LIMIT', 2000),

];
