<?php

namespace App\Services\Newsletter;

use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriberEnrollmentService
{
    public function __construct(
        private readonly GroupEngagementService $groupEngagement,
    ) {}

    /**
     * @param  array<int, int>  $managedIds
     * @param  array<int, int>  $selectedIds
     * @param  array<string, mixed>  $metadata
     * @return array{subscriber: Subscriber, status: string, subscriber_group_id: int}
     */
    public function enroll(SubscriberGroup $group, array $managedIds, array $selectedIds, array $names, array $metadata, Request $request): array
    {
        return $this->enrollPreferences($managedIds, $selectedIds, $names, $metadata, $request, $group->getKey());
    }

    public function enrollPreferences(array $managedIds, array $selectedIds, array $names, array $metadata, Request $request, ?int $groupId = null): array
    {
        return DB::transaction(function () use ($groupId, $managedIds, $selectedIds, $names, $metadata, $request): array {
            $subscriber = Subscriber::query()->firstOrNew([
                'email' => strtolower(trim((string) ($names['email'] ?? ''))),
            ]);
            $wasExisting = $subscriber->exists;
            $previousStatus = $subscriber->status;
            if (in_array($previousStatus, ['erased', 'bounced', 'complained'], true)) {
                throw ValidationException::withMessages([
                    'email' => ['This address cannot be subscribed through a public form.'],
                ]);
            }
            if ($wasExisting && $selectedIds !== []) {
                $organisationIds = \App\Models\SubscriberSubGroup::query()->whereKey($selectedIds)
                    ->with('group')->get()->pluck('group.organisation_id')->filter()->unique();
                foreach ($organisationIds as $organisationId) {
                    if (app(OrganisationMailOptOut::class)->has((int) $organisationId, (int) $subscriber->getKey())) {
                        throw ValidationException::withMessages([
                            'email' => ['This address opted out of this organisation. Use your preferences link to opt back in.'],
                        ]);
                    }
                }
            }
            $previousFirstName = $subscriber->first_name;
            $previousLastName = $subscriber->last_name;
            $previousManagedIds = $wasExisting
                ? $subscriber->allSubGroups()
                    ->whereIn('subscriber_sub_groups.id', $managedIds)
                    ->wherePivotNull('subscriber_sub_group.unsubscribed_at')
                    ->pluck('subscriber_sub_groups.id')->sort()->values()->all()
                : [];

            $targetStatus = 'active';
            $existingMetadata = $subscriber->metadata ?? [];
            if (array_key_exists('newsletter_form', $metadata)) {
                unset($existingMetadata['product_form']);
            }
            if (array_key_exists('product_form', $metadata)) {
                unset($existingMetadata['newsletter_form']);
            }
            $subscriber->fill([
                'first_name' => $names['first_name'] ?? null,
                'last_name' => $names['last_name'] ?? null,
                'status' => $targetStatus,
                'confirmed_at' => $subscriber->confirmed_at,
                'unsubscribed_at' => null,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 65535, ''),
                'metadata' => array_merge($existingMetadata, $metadata),
            ]);
            $subscriber->ensureConfirmationToken();
            $subscriber->save();

            $subscriber->forceFill([
                'pending_confirmation_expires_at' => null,
                'pending_confirmation_last_resent_at' => null,
                'pending_confirmation_resend_count' => 0,
                'pending_lifecycle_state' => null,
            ])->save();

            $existingManaged = $subscriber->allSubGroups()
                ->whereIn('subscriber_sub_groups.id', $managedIds)
                ->pluck('subscriber_sub_groups.id')->all();

            foreach (array_intersect($selectedIds, $existingManaged) as $id) {
                $subscriber->allSubGroups()->updateExistingPivot($id, [
                    'subscribed_at' => now(), 'unsubscribed_at' => null,
                ]);
            }
            if ($toAdd = array_diff($selectedIds, $existingManaged)) {
                $subscriber->subGroups()->attach($toAdd, [
                    'subscribed_at' => now(), 'unsubscribed_at' => null,
                ]);
            }
            if ($toRemove = array_diff($existingManaged, $selectedIds)) {
                $subscriber->allSubGroups()->updateExistingPivot($toRemove, ['unsubscribed_at' => now()]);
            }

            $groupIds = \App\Models\SubscriberSubGroup::query()
                ->whereIn('id', array_unique(array_merge($managedIds, $selectedIds)))
                ->pluck('subscriber_group_id')->unique()->all();
            $this->groupEngagement->syncForSubscriber($subscriber, $groupIds);

            $subscriber = $subscriber->fresh(['subGroups.group']);
            $currentManagedIds = $subscriber->subGroups
                ->whereIn('id', $managedIds)
                ->pluck('id')->sort()->values()->all();
            $namesChanged = $this->normalize($previousFirstName) !== $this->normalize($subscriber->first_name)
                || $this->normalize($previousLastName) !== $this->normalize($subscriber->last_name);

            return [
                'subscriber' => $subscriber,
                'subscriber_group_id' => $groupId,
                'status' => match (true) {
                    ! $wasExisting => 'subscribed',
                    $previousStatus === 'unsubscribed' => 'resubscribed',
                    $namesChanged || $previousManagedIds !== $currentManagedIds => 'subscription_updated',
                    default => 'already_subscribed',
                },
            ];
        });
    }

    private function normalize(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' ? null : $value;
    }
}
