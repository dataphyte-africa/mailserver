<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Organisation;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\GroupEngagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RestoreFoundationPrivateAudiences extends Command
{
    protected $signature = 'newsletter:restore-foundation-private-audiences {--apply : Copy the original Foundation buckets into named private audiences}';

    protected $description = 'Restore each original Foundation preference bucket as a separate private audience without changing legacy campaign targets.';

    public function handle(GroupEngagementService $engagement): int
    {
        $organisation = Organisation::query()->where('primary_collection_handle', 'foundation_newsletters')->sole();
        $legacy = SubscriberGroup::query()->where('slug', 'foundation')->sole();
        if ($legacy->product_id !== null || ($legacy->organisation_id !== null
            && (int) $legacy->organisation_id !== (int) $organisation->id)) {
            $this->error('The legacy Foundation group has unexpected ownership. No changes made.');

            return self::FAILURE;
        }

        $sources = $legacy->subGroups()->where('purpose', 'preference')->orderBy('id')->get();
        if ($sources->isEmpty()) {
            $this->error('No original Foundation preference buckets found. No changes made.');

            return self::FAILURE;
        }

        foreach ($sources as $source) {
            $count = DB::table('subscriber_sub_group')->where('subscriber_sub_group_id', $source->id)
                ->whereNull('unsubscribed_at')->count();
            $this->line("{$source->name}: {$count} active memberships");
        }

        if (! $this->option('apply')) {
            $this->info('Preview only. Legacy groups, memberships, and campaign targets are unchanged.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($organisation, $legacy, $sources, $engagement): void {
            foreach ($sources as $source) {
                $slug = 'foundation-private-'.$source->slug;
                $group = SubscriberGroup::query()->where('slug', $slug)->first();
                if ($group && ((int) $group->organisation_id !== (int) $organisation->id
                    || $group->product_id !== null || ! $group->isPrivateAudience()
                    || $group->archived_at !== null)) {
                    throw new RuntimeException("Conflicting audience {$slug}. No changes made.");
                }

                $group ??= SubscriberGroup::query()->create([
                    'organisation_id' => $organisation->id,
                    'product_id' => null,
                    'audience_scope' => 'organisation_private',
                    'name' => $source->name,
                    'slug' => $slug,
                    'collection_handle' => $legacy->collection_handle,
                    'description' => "Restored from Foundation / {$source->name} (legacy bucket {$source->id}).",
                    'is_public_preference' => false,
                ]);
                $members = SubscriberSubGroup::query()->firstOrCreate(
                    ['subscriber_group_id' => $group->id, 'slug' => 'members'],
                    ['name' => 'Members', 'purpose' => 'preference'],
                );
                if ($members->purpose !== 'preference' || $members->archived_at !== null) {
                    throw new RuntimeException("Conflicting membership bucket for {$source->name}. No changes made.");
                }

                $engagement->ensureSegments($group);
                $segments = $group->subGroups()->where('purpose', 'engagement')->get()
                    ->mapWithKeys(fn (SubscriberSubGroup $segment) => [$segment->slug => $segment->id]);
                DB::table('subscriber_sub_group')->where('subscriber_sub_group_id', $source->id)
                    ->orderBy('id')->chunkById(500, function ($rows) use ($legacy, $organisation, $group, $members, $segments): void {
                        $ids = $rows->pluck('subscriber_id')->all();
                        $ratings = DB::table('subscriber_group_engagements')
                            ->where('subscriber_group_id', $legacy->id)->whereIn('subscriber_id', $ids)
                            ->get()->keyBy('subscriber_id');
                        $optedOut = DB::table('organisation_subscriber_opt_outs')
                            ->where('organisation_id', $organisation->id)->whereIn('subscriber_id', $ids)
                            ->pluck('subscriber_id')->flip();
                        $now = now();
                        $memberships = [];
                        $engagements = [];
                        $ratingMemberships = [];
                        foreach ($rows as $row) {
                            $prior = $ratings->get($row->subscriber_id);
                            $left = $row->unsubscribed_at !== null || $optedOut->has($row->subscriber_id);
                            $rating = $prior?->rating === 'suppressed' ? 'suppressed'
                                : ($left ? 'unsubscribed' : ($prior?->rating ?? 'new'));
                            $memberships[] = [
                                'subscriber_id' => $row->subscriber_id,
                                'subscriber_sub_group_id' => $members->id,
                                'subscribed_at' => $row->subscribed_at,
                                'unsubscribed_at' => $row->unsubscribed_at,
                                'private_source' => $row->private_source,
                            ];
                            $engagements[] = [
                                'subscriber_id' => $row->subscriber_id,
                                'subscriber_group_id' => $group->id,
                                'rating' => $rating,
                                'is_member' => ! $left,
                                'score' => $prior?->score ?? 0,
                                'first_joined_at' => $prior?->first_joined_at ?? $row->subscribed_at,
                                'last_engaged_at' => $prior?->last_engaged_at,
                                'last_recalculated_at' => $now,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                            $ratingMemberships[] = [
                                'subscriber_id' => $row->subscriber_id,
                                'subscriber_sub_group_id' => $segments->get('rating-'.$rating),
                                'subscribed_at' => $now,
                                'unsubscribed_at' => null,
                                'private_source' => null,
                            ];
                        }
                        DB::table('subscriber_sub_group')->insertOrIgnore($memberships);
                        DB::table('subscriber_group_engagements')->insertOrIgnore($engagements);
                        DB::table('subscriber_sub_group')->insertOrIgnore($ratingMemberships);
                    });

                $sourceCount = DB::table('subscriber_sub_group')->where('subscriber_sub_group_id', $source->id)->count();
                $targetCount = DB::table('subscriber_sub_group')->where('subscriber_sub_group_id', $members->id)->count();
                if ($sourceCount !== $targetCount) {
                    throw new RuntimeException("Membership count mismatch for {$source->name}. No changes made.");
                }
                $this->info("Restored {$source->name}: {$targetCount} membership records.");
            }
        });

        $this->info('Original Foundation buckets are now separate private audiences. Legacy records and campaign targets were not changed.');

        return self::SUCCESS;
    }
}
