<?php

namespace App\Services\Newsletter;

use App\Models\Subscriber;
use Carbon\CarbonInterface;

class PendingSubscriberLifecycleService
{
    public const MAX_RESENDS = 3;
    public const COOLDOWN_MINUTES = 15;
    public const EXPIRY_DAYS = 7;

    public const STATE_AWAITING_CONFIRMATION = 'awaiting_confirmation';
    public const STATE_AWAITING_RECONFIRMATION = 'awaiting_reconfirmation';
    public const STATE_CONFIRMATION_RESENT = 'confirmation_resent';
    public const STATE_RECONFIRMATION_RESENT = 'reconfirmation_resent';
    public const STATE_DELIVERY_CONFIRMED = 'delivery_confirmed';
    public const STATE_EXPIRED_PENDING = 'expired_pending';

    public function resetForPending(Subscriber $subscriber, string $origin = 'subscribed', ?CarbonInterface $now = null): bool
    {
        $now ??= now();

        $subscriber->forceFill([
            'pending_confirmation_resend_count' => 0,
            'pending_confirmation_last_resent_at' => null,
            'pending_confirmation_expires_at' => $now->copy()->addDays(self::EXPIRY_DAYS),
            'pending_lifecycle_state' => $origin === 'resubscribed'
                ? self::STATE_AWAITING_RECONFIRMATION
                : self::STATE_AWAITING_CONFIRMATION,
        ]);

        return $this->saveIfDirty($subscriber);
    }

    public function syncState(Subscriber $subscriber, ?CarbonInterface $now = null): bool
    {
        $now ??= now();

        if ($subscriber->status !== 'pending') {
            return false;
        }

        $this->ensurePendingBaseline($subscriber, $now);

        if ($this->isExpired($subscriber, $now)) {
            $subscriber->forceFill([
                'pending_lifecycle_state' => self::STATE_EXPIRED_PENDING,
            ]);
        }

        return $this->saveIfDirty($subscriber);
    }

    public function resendDecision(Subscriber $subscriber, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $this->syncState($subscriber, $now);

        if ($subscriber->status !== 'pending') {
            return $this->decision(false, 'Only pending subscribers can receive confirmation resends.');
        }

        if ($this->isExpired($subscriber, $now)) {
            return $this->decision(false, 'This pending subscriber has expired and can no longer be activated from a resend.');
        }

        if (($subscriber->pending_confirmation_resend_count ?? 0) >= self::MAX_RESENDS) {
            return $this->decision(false, 'This pending subscriber has already used the maximum 3 confirmation resends.');
        }

        $cooldownEndsAt = $this->cooldownEndsAt($subscriber);

        if ($cooldownEndsAt && $cooldownEndsAt->isFuture()) {
            return $this->decision(
                false,
                sprintf(
                    'This subscriber is still in the 15 minute resend cooldown. Try again %s.',
                    $cooldownEndsAt->diffForHumans($now, null, false, 2)
                )
            );
        }

        return [
            'eligible' => true,
            'message' => null,
            'remaining_resends' => self::MAX_RESENDS - (int) ($subscriber->pending_confirmation_resend_count ?? 0),
            'cooldown_ends_at' => $cooldownEndsAt,
        ];
    }

    public function markConfirmationResent(Subscriber $subscriber, ?CarbonInterface $now = null): bool
    {
        $now ??= now();
        $this->ensurePendingBaseline($subscriber, $now);

        $subscriber->forceFill([
            'pending_confirmation_resend_count' => (int) ($subscriber->pending_confirmation_resend_count ?? 0) + 1,
            'pending_confirmation_last_resent_at' => $now,
            'pending_lifecycle_state' => $this->confirmationEmailStatus($subscriber) === 'resubscribed'
                ? self::STATE_RECONFIRMATION_RESENT
                : self::STATE_CONFIRMATION_RESENT,
        ]);

        return $this->saveIfDirty($subscriber);
    }

    public function markDeliveryConfirmed(Subscriber $subscriber, CarbonInterface $eventDate): bool
    {
        $subscriber->forceFill([
            'status' => 'active',
            'confirmed_at' => $subscriber->confirmed_at ?? $eventDate,
            'unsubscribed_at' => null,
            'pending_lifecycle_state' => self::STATE_DELIVERY_CONFIRMED,
        ]);

        return $this->saveIfDirty($subscriber);
    }

    public function isExpired(Subscriber $subscriber, ?CarbonInterface $now = null): bool
    {
        $now ??= now();

        return $subscriber->status === 'pending'
            && $subscriber->confirmed_at === null
            && $subscriber->pending_confirmation_expires_at !== null
            && $subscriber->pending_confirmation_expires_at->lessThanOrEqualTo($now);
    }

    public function confirmationEmailStatus(Subscriber $subscriber): string
    {
        return in_array($subscriber->pending_lifecycle_state, [
            self::STATE_AWAITING_RECONFIRMATION,
            self::STATE_RECONFIRMATION_RESENT,
        ], true) ? 'resubscribed' : 'subscribed';
    }

    public function snapshot(Subscriber $subscriber, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $this->syncState($subscriber, $now);
        $decision = $this->resendDecision($subscriber, $now);
        $isPending = $subscriber->status === 'pending';
        $isExpired = $this->isExpired($subscriber, $now);
        $expiresAt = $subscriber->pending_confirmation_expires_at;
        $lastResentAt = $subscriber->pending_confirmation_last_resent_at;

        return [
            'is_pending' => $isPending,
            'is_expired' => $isExpired,
            'state' => $subscriber->pending_lifecycle_state,
            'label' => $this->labelForState($subscriber),
            'age_label' => $isPending ? sprintf(
                'Pending for %s',
                $subscriber->created_at?->diffForHumans($now, \Carbon\CarbonInterface::DIFF_ABSOLUTE, false, 2) ?? 'an unknown period'
            ) : null,
            'expiry_label' => $expiresAt
                ? ($isExpired ? sprintf('Expired %s', $expiresAt->diffForHumans($now, null, false, 2)) : sprintf('Expires %s', $expiresAt->diffForHumans($now, null, false, 2)))
                : null,
            'expires_at' => $expiresAt,
            'resend_count' => (int) ($subscriber->pending_confirmation_resend_count ?? 0),
            'resends_remaining' => max(0, self::MAX_RESENDS - (int) ($subscriber->pending_confirmation_resend_count ?? 0)),
            'last_resent_at' => $lastResentAt,
            'last_resent_label' => $lastResentAt ? $lastResentAt->diffForHumans($now, null, false, 2) : null,
            'cooldown_ends_at' => $this->cooldownEndsAt($subscriber),
            'can_resend' => (bool) ($decision['eligible'] ?? false),
            'resend_block_message' => $decision['message'] ?? null,
            'activation_notice' => 'Activation still requires a delivered, opened, or clicked confirmation webhook from the signup lifecycle email.',
        ];
    }

    private function ensurePendingBaseline(Subscriber $subscriber, CarbonInterface $now): void
    {
        if ($subscriber->status !== 'pending') {
            return;
        }

        if ($subscriber->pending_confirmation_expires_at === null) {
            $baseline = $subscriber->created_at?->copy() ?? $now->copy();
            $subscriber->pending_confirmation_expires_at = $baseline->addDays(self::EXPIRY_DAYS);
        }

        if (! filled($subscriber->pending_lifecycle_state)) {
            $subscriber->pending_lifecycle_state = self::STATE_AWAITING_CONFIRMATION;
        }
    }

    private function cooldownEndsAt(Subscriber $subscriber): ?CarbonInterface
    {
        if (! $subscriber->pending_confirmation_last_resent_at) {
            return null;
        }

        return $subscriber->pending_confirmation_last_resent_at->copy()->addMinutes(self::COOLDOWN_MINUTES);
    }

    private function labelForState(Subscriber $subscriber): string
    {
        return match ($subscriber->pending_lifecycle_state) {
            self::STATE_AWAITING_RECONFIRMATION => 'Awaiting resubscribe confirmation',
            self::STATE_CONFIRMATION_RESENT => 'Confirmation resent',
            self::STATE_RECONFIRMATION_RESENT => 'Resubscribe confirmation resent',
            self::STATE_DELIVERY_CONFIRMED => 'Delivery confirmed',
            self::STATE_EXPIRED_PENDING => 'Expired pending',
            default => 'Awaiting confirmation',
        };
    }

    private function decision(bool $eligible, ?string $message): array
    {
        return [
            'eligible' => $eligible,
            'message' => $message,
        ];
    }

    private function saveIfDirty(Subscriber $subscriber): bool
    {
        if (! $subscriber->isDirty()) {
            return false;
        }

        $subscriber->save();

        return true;
    }
}
