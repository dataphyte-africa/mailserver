<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Services\Newsletter\CampaignWorkflowService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CampaignWorkflowServiceTest extends TestCase
{
    public function test_campaigns_must_be_approved_before_regular_send(): void
    {
        $workflow = new CampaignWorkflowService;
        $author = new WorkflowUser([]);

        $draft = new Campaign;
        $draft->status = CampaignWorkflowService::DRAFT;

        $this->expectException(ValidationException::class);
        $workflow->assertCanSend($author, $draft);
    }

    public function test_approved_campaign_can_be_sent_by_regular_operator(): void
    {
        $workflow = new CampaignWorkflowService;
        $author = new WorkflowUser([]);

        $approved = new Campaign;
        $approved->status = CampaignWorkflowService::APPROVED;

        $workflow->assertCanSend($author, $approved);
        $this->assertSame(CampaignWorkflowService::SCHEDULED, $workflow->statusForAuthorAction($author, 'schedule', $approved));
    }

    public function test_reviewer_and_approver_roles_control_review_transitions(): void
    {
        $workflow = new CampaignWorkflowService;
        $reviewer = new WorkflowUser(['reviewer']);
        $approver = new WorkflowUser(['approver']);

        $campaign = new Campaign;
        $campaign->status = CampaignWorkflowService::IN_REVIEW;

        $workflow->assertCanRequestChanges($reviewer, $campaign);
        $workflow->assertCanApprove($approver, $campaign);
        $this->assertSame(CampaignWorkflowService::SCHEDULED, $workflow->statusForAuthorAction($approver, 'schedule', $campaign));
    }

    public function test_super_operator_can_bypass_approval_for_schedule(): void
    {
        $workflow = new CampaignWorkflowService;
        $super = new WorkflowUser([], true);
        $campaign = new Campaign;
        $campaign->status = CampaignWorkflowService::DRAFT;

        $this->assertSame(CampaignWorkflowService::SCHEDULED, $workflow->statusForAuthorAction($super, 'schedule', $campaign));
    }

    public function test_sender_role_does_not_bypass_campaign_approval(): void
    {
        $workflow = new CampaignWorkflowService;
        $sender = new WorkflowUser(['sender']);
        $campaign = new Campaign;
        $campaign->status = CampaignWorkflowService::DRAFT;

        $this->expectException(ValidationException::class);
        $workflow->assertCanSend($sender, $campaign);
    }
}

class WorkflowUser implements Authenticatable
{
    public function __construct(private array $roles, private bool $super = false)
    {
    }

    public function isSuper(): bool
    {
        return $this->super;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return 1;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}
