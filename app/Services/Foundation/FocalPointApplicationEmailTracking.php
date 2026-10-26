<?php

namespace App\Services\Foundation;

use App\Models\FocalPointApplication;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FocalPointApplicationEmailTracking
{
    public function apply(WebhookLog $log, string $event): bool
    {
        $payload = $log->payload ?? [];
        if (($payload['lifecycle_email'] ?? null) !== 'focal_point_application_receipt') {
            return false;
        }

        // The public webhook must have a configured shared secret before it can change application state.
        if (blank(config('newsletter.webhook_secret'))) {
            return true;
        }

        $reference = $payload['focal_point_application_id'] ?? null;
        $application = is_string($reference) && Str::isUuid($reference)
            ? FocalPointApplication::query()->where('uuid', $reference)->first()
            : null;

        if (! $application || ! $log->to_email || ! hash_equals($application->email, Str::lower($log->to_email))) {
            return true;
        }

        $eventAt = filled($payload['date'] ?? $payload['Date'] ?? null)
            ? Carbon::parse($payload['date'] ?? $payload['Date'])
            : now();

        if (in_array($event, ['opened', 'clicked'], true)) {
            $application->forceFill([
                'confirmation_email_status' => 'opened',
                'confirmation_email_delivered_at' => $application->confirmation_email_delivered_at ?? $eventAt,
                'confirmation_email_opened_at' => $application->confirmation_email_opened_at ?? $eventAt,
                'confirmation_email_failed_at' => null,
            ])->save();
        } elseif ($event === 'delivered' && $application->confirmation_email_status !== 'opened') {
            $application->forceFill([
                'confirmation_email_status' => 'delivered',
                'confirmation_email_delivered_at' => $application->confirmation_email_delivered_at ?? $eventAt,
                'confirmation_email_failed_at' => null,
            ])->save();
        } elseif (in_array($event, ['failed', 'bounced'], true) && $application->confirmation_email_status !== 'opened') {
            $application->forceFill([
                'confirmation_email_status' => 'failed',
                'confirmation_email_failed_at' => $application->confirmation_email_failed_at ?? $eventAt,
            ])->save();
        }

        return true;
    }
}
