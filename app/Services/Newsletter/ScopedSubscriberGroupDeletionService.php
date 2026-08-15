<?php

namespace App\Services\Newsletter;

use App\Models\CampaignAudience;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ScopedSubscriberGroupDeletionService
{
    public function __construct(
        private readonly ScopedSubscriberGroupProductSelector $products,
    ) {}

    public function delete(
        ?Authenticatable $operator,
        SubscriberGroup $group,
    ): bool {
        if ($this->products->resolveGroup($operator, $group) === null) {
            return false;
        }

        if (! $this->canDeleteGroup($group)) {
            return false;
        }

        return $group->deleteOrFail();
    }

    public function archive(
        ?Authenticatable $operator,
        SubscriberGroup $group,
    ): bool {
        if ($this->products->resolveGroup($operator, $group) === null) {
            return false;
        }

        if (! $this->wasGroupOrChildTargetedByCampaign($group)) {
            return false;
        }

        return $this->archiveModel($group, $operator);
    }

    public function deleteSubGroup(
        ?Authenticatable $operator,
        SubscriberGroup $group,
        SubscriberSubGroup $subGroup,
    ): bool {
        if ($this->products->resolveSubGroup($operator, $group, $subGroup) === null) {
            return false;
        }

        if (! $this->canDeleteSubGroup($subGroup)) {
            return false;
        }

        return $subGroup->deleteOrFail();
    }

    public function archiveSubGroup(
        ?Authenticatable $operator,
        SubscriberGroup $group,
        SubscriberSubGroup $subGroup,
    ): bool {
        if ($this->products->resolveSubGroup($operator, $group, $subGroup) === null) {
            return false;
        }

        if (! $this->wasTargetedByCampaign($subGroup)) {
            return false;
        }

        return $this->archiveModel($subGroup, $operator);
    }

    public function canDeleteGroup(SubscriberGroup $group): bool
    {
        if ($this->wasTargetedByCampaign($group)) {
            return false;
        }

        if ($group->subGroups()->whereHas('subscribers')->exists()) {
            return false;
        }

        return $group->subGroups()
            ->get()
            ->every(fn (SubscriberSubGroup $subGroup): bool => ! $this->wasTargetedByCampaign($subGroup));
    }

    public function canDeleteSubGroup(SubscriberSubGroup $subGroup): bool
    {
        return ! $this->wasTargetedByCampaign($subGroup)
            && ! $subGroup->subscribers()->exists();
    }

    private function wasTargetedByCampaign(Model $targetable): bool
    {
        return CampaignAudience::query()
            ->where('targetable_id', $targetable->getKey())
            ->whereIn('targetable_type', $this->targetableTypes($targetable))
            ->exists();
    }

    private function wasGroupOrChildTargetedByCampaign(SubscriberGroup $group): bool
    {
        if ($this->wasTargetedByCampaign($group)) {
            return true;
        }

        return $group->subGroups()
            ->get()
            ->contains(fn (SubscriberSubGroup $subGroup): bool => $this->wasTargetedByCampaign($subGroup));
    }

    private function archiveModel(Model $model, ?Authenticatable $operator): bool
    {
        if ($model->getAttribute('archived_at') !== null) {
            return true;
        }

        return $model->forceFill([
            'archived_at' => Carbon::now(),
            'archived_by' => $this->operatorKey($operator),
        ])->save();
    }

    private function operatorKey(?Authenticatable $operator): ?int
    {
        $identifier = $operator?->getAuthIdentifier();

        return is_numeric($identifier) ? (int) $identifier : null;
    }

    /**
     * @return array<int, class-string<Model>|string>
     */
    private function targetableTypes(Model $targetable): array
    {
        $types = [
            $targetable->getMorphClass(),
            $targetable::class,
        ];

        if ($targetable instanceof SubscriberGroup) {
            $types[] = 'subscriber_group';
        }

        if ($targetable instanceof SubscriberSubGroup) {
            $types[] = 'subscriber_sub_group';
        }

        return array_values(array_unique($types));
    }
}
