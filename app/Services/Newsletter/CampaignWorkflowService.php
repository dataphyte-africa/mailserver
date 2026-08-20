<?php

namespace App\Services\Newsletter;

use App\Models\Campaign;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

class CampaignWorkflowService
{
    public const DRAFT = 'draft';
    public const IN_REVIEW = 'in_review';
    public const CHANGES_REQUESTED = 'changes_requested';
    public const APPROVED = 'approved';
    public const SCHEDULED = 'scheduled';
    public const SENDING = 'sending';
    public const SENT = 'sent';
    public const PARTIAL = 'partial';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    public function statusForAuthorAction(?Authenticatable $operator, string $action, ?Campaign $campaign = null): string
    {
        return match ($action) {
            'submit_review' => self::IN_REVIEW,
            'schedule' => $this->canBypassApproval($operator) || $campaign?->status === self::APPROVED
                ? self::SCHEDULED
                : $this->reject('Campaign must be approved before it can be scheduled.'),
            'send' => $this->canBypassApproval($operator) || in_array($campaign?->status, [self::APPROVED, self::SCHEDULED], true)
                ? ($campaign?->status ?? self::DRAFT)
                : $this->reject('Campaign must be approved before it can be sent.'),
            default => self::DRAFT,
        };
    }

    public function assertCanEdit(Campaign $campaign): void
    {
        if (! in_array($campaign->status, [self::DRAFT, self::CHANGES_REQUESTED, self::APPROVED, self::SCHEDULED], true)) {
            $this->reject('Only draft, changes requested, approved, or scheduled campaigns can be edited.');
        }
    }

    public function assertCanSchedule(?Authenticatable $operator, Campaign $campaign): void
    {
        if ($campaign->status === self::APPROVED || $this->canBypassApproval($operator)) {
            return;
        }

        $this->reject('Campaign must be approved before it can be scheduled.');
    }

    public function assertCanSend(?Authenticatable $operator, Campaign $campaign): void
    {
        if (in_array($campaign->status, [self::APPROVED, self::SCHEDULED], true) || $this->canBypassApproval($operator)) {
            return;
        }

        $this->reject('Campaign must be approved before it can be sent.');
    }

    public function assertCanRequestChanges(?Authenticatable $operator, Campaign $campaign): void
    {
        if ($campaign->status !== self::IN_REVIEW) {
            $this->reject('Only campaigns in review can have changes requested.');
        }

        if (! $this->canReview($operator)) {
            $this->reject('You do not have permission to request campaign changes.');
        }
    }

    public function assertCanApprove(?Authenticatable $operator, Campaign $campaign): void
    {
        if (! in_array($campaign->status, [self::IN_REVIEW, self::CHANGES_REQUESTED], true)) {
            $this->reject('Only reviewed campaigns can be approved.');
        }

        if (! $this->canApprove($operator)) {
            $this->reject('You do not have permission to approve campaigns.');
        }
    }

    public function assertCanCancelSchedule(Campaign $campaign): void
    {
        if ($campaign->status !== self::SCHEDULED) {
            $this->reject('Only scheduled campaigns can be cancelled.');
        }
    }

    public function assertCanReset(Campaign $campaign): void
    {
        if (! in_array($campaign->status, [self::SENDING, self::FAILED], true)) {
            $this->reject('Only campaigns stuck in sending or failed state can be reset.');
        }
    }

    public function canBypassApproval(?Authenticatable $operator): bool
    {
        if ($this->isSuper($operator)) {
            return true;
        }

        if (! $operator) {
            return false;
        }

        return $this->hasAnyRole($operator, ['super_admin', 'approver']);
    }

    public function canReview(?Authenticatable $operator): bool
    {
        return $this->isSuper($operator) || ($operator && $this->hasAnyRole($operator, ['super_admin', 'reviewer', 'approver']));
    }

    public function canApprove(?Authenticatable $operator): bool
    {
        return $this->isSuper($operator) || ($operator && $this->hasAnyRole($operator, ['super_admin', 'approver']));
    }

    private function isSuper(?Authenticatable $operator): bool
    {
        return $operator !== null && method_exists($operator, 'isSuper') && $operator->isSuper();
    }

    private function hasAnyRole(Authenticatable $operator, array $roles): bool
    {
        foreach ($roles as $role) {
            if (method_exists($operator, 'hasRole') && $operator->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'workflow' => $message,
        ]);
    }
}
