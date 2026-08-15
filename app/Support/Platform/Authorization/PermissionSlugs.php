<?php

namespace App\Support\Platform\Authorization;

final class PermissionSlugs
{
    public const PLATFORM_ADMIN = 'platform_admin';
    public const ORGANISATION_MANAGE = 'organisation_manage';
    public const PRODUCT_MANAGE = 'product_manage';
    public const ACCESS_SCOPE_MANAGE = 'access_scope_manage';
    public const NEWSLETTER_VIEW = 'newsletter_view';
    public const NEWSLETTER_CREATE = 'newsletter_create';
    public const NEWSLETTER_EDIT = 'newsletter_edit';
    public const NEWSLETTER_REVIEW = 'newsletter_review';
    public const NEWSLETTER_APPROVE = 'newsletter_approve';
    public const NEWSLETTER_SCHEDULE = 'newsletter_schedule';
    public const NEWSLETTER_SEND = 'newsletter_send';
    public const NEWSLETTER_RETRY = 'newsletter_retry';
    public const NEWSLETTER_ARCHIVE = 'newsletter_archive';
    public const FORM_VIEW = 'form_view';
    public const FORM_CREATE = 'form_create';
    public const FORM_EDIT = 'form_edit';
    public const FORM_PUBLISH = 'form_publish';
    public const SUBMISSION_VIEW = 'submission_view';
    public const SUBMISSION_REVIEW = 'submission_review';
    public const SUBMISSION_APPROVE = 'submission_approve';
    public const SUBMISSION_EXPORT = 'submission_export';
    public const SUBMISSION_CLOSE = 'submission_close';
    public const SUBSCRIBER_VIEW = 'subscriber_view';
    public const SUBSCRIBER_MANAGE = 'subscriber_manage';
    public const AUDIENCE_MANAGE = 'audience_manage';
    public const PREFERENCE_MANAGE = 'preference_manage';
    public const ANALYTICS_VIEW = 'analytics_view';
    public const ANALYTICS_EXPORT = 'analytics_export';
    public const DOMAIN_MANAGE = 'domain_manage';
    public const INTEGRATION_MANAGE = 'integration_manage';

    /**
     * @return array<string, array<int, string>>
     */
    public static function categories(): array
    {
        return [
            'platform' => [
                self::PLATFORM_ADMIN,
            ],
            'organisation' => [
                self::ORGANISATION_MANAGE,
                self::PRODUCT_MANAGE,
                self::ACCESS_SCOPE_MANAGE,
            ],
            'newsletter' => [
                self::NEWSLETTER_VIEW,
                self::NEWSLETTER_CREATE,
                self::NEWSLETTER_EDIT,
                self::NEWSLETTER_REVIEW,
                self::NEWSLETTER_APPROVE,
                self::NEWSLETTER_SCHEDULE,
                self::NEWSLETTER_SEND,
                self::NEWSLETTER_RETRY,
                self::NEWSLETTER_ARCHIVE,
            ],
            'forms' => [
                self::FORM_VIEW,
                self::FORM_CREATE,
                self::FORM_EDIT,
                self::FORM_PUBLISH,
                self::SUBMISSION_VIEW,
                self::SUBMISSION_REVIEW,
                self::SUBMISSION_APPROVE,
                self::SUBMISSION_EXPORT,
                self::SUBMISSION_CLOSE,
            ],
            'audience' => [
                self::SUBSCRIBER_VIEW,
                self::SUBSCRIBER_MANAGE,
                self::AUDIENCE_MANAGE,
                self::PREFERENCE_MANAGE,
            ],
            'analytics' => [
                self::ANALYTICS_VIEW,
                self::ANALYTICS_EXPORT,
            ],
            'operations' => [
                self::DOMAIN_MANAGE,
                self::INTEGRATION_MANAGE,
            ],
        ];
    }
}
