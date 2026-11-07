<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\GroupEngagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;

class ProvisionFoundationPolicyPoint extends Command
{
    protected $signature = 'newsletter:provision-foundation-policy-point {--apply : Create the Foundation product and public audience}';

    protected $description = 'Provision Foundation Policy Point independently of the Policy Point organisation.';

    public function handle(GroupEngagementService $engagement): int
    {
        $organisation = Organisation::query()->where('primary_collection_handle', 'foundation_newsletters')->first();
        if (! $organisation || $organisation->status !== 'active') {
            $this->error('An active Foundation organisation is required. No changes made.');

            return self::FAILURE;
        }

        if (! view()->exists('emails.foundation.policy-point')
            || ! is_file(resource_path('blueprints/collections/foundation_newsletters/policy_point.yaml'))) {
            $this->error('The Foundation Policy Point blueprint and email view must be deployed first. No changes made.');

            return self::FAILURE;
        }

        if (! Collection::findByHandle('foundation_newsletters')) {
            $this->error('The Foundation newsletter collection is not registered. No changes made.');

            return self::FAILURE;
        }

        $productSlug = 'foundation-newsletters-policy-point';
        $groupSlug = 'foundation-policy-point-subscribers';
        $product = Product::query()->where('slug', $productSlug)->first();
        if ($product && ((int) $product->organisation_id !== (int) $organisation->id
            || $product->primary_collection_handle !== 'foundation_newsletters'
            || $product->blueprint_handle !== 'policy_point'
            || $product->product_type !== 'newsletter' || $product->status !== 'active')) {
            $this->error('Product slug belongs to another owner or blueprint. No changes made.');

            return self::FAILURE;
        }

        if (Product::query()->where('primary_collection_handle', 'foundation_newsletters')
            ->where('blueprint_handle', 'policy_point')->when($product, fn ($query) => $query->whereKeyNot($product->id))->exists()) {
            $this->error('The Foundation Policy Point blueprint is already assigned to another product. No changes made.');

            return self::FAILURE;
        }

        $group = SubscriberGroup::query()->where('slug', $groupSlug)->first();
        if ($group && ((int) $group->organisation_id !== (int) $organisation->id
            || ($product && (int) $group->product_id !== (int) $product->id)
            || $group->collection_handle !== 'foundation_newsletters'
            || $group->audience_scope !== 'product_public' || ! $group->is_public_preference
            || $group->isArchived())) {
            $this->error('Audience slug belongs to another owner or is not an active public product audience. No changes made.');

            return self::FAILURE;
        }
        if ($group && (! $product || $group->subGroups()->where('purpose', 'preference')
            ->where('slug', '!=', 'newsletter')->exists()
            || $group->subGroups()->where('slug', 'newsletter')
                ->where(fn ($query) => $query->where('purpose', '!=', 'preference')->orWhereNotNull('archived_at'))
                ->exists())) {
            $this->error('The existing audience has conflicting preference subgroups. No changes made.');

            return self::FAILURE;
        }
        if ($product && SubscriberGroup::query()->where('product_id', $product->id)
            ->when($group, fn ($query) => $query->whereKeyNot($group->id))->exists()) {
            $this->error('The product already has a different audience. No changes made.');

            return self::FAILURE;
        }

        $this->line("Foundation: {$organisation->name}; product: {$productSlug}; audience: {$groupSlug}.");
        $this->line('Blueprint: '.(Blueprint::find('collections.foundation_newsletters.policy_point') ? 'registered' : 'needs registration'));
        $this->line('No subscribers or Policy Point organisation records will be changed.');
        if (! $this->option('apply')) {
            $this->info('Preview only. Re-run with --apply to provision.');

            return self::SUCCESS;
        }

        if (! Blueprint::find('collections.foundation_newsletters.policy_point')) {
            if (Artisan::call('newsletter:sync-checked-in-blueprint', [
                'collection' => 'foundation_newsletters', 'blueprint' => 'policy_point',
            ]) !== self::SUCCESS) {
                $this->error(trim(Artisan::output()));

                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($organisation, $productSlug, $groupSlug, $engagement): void {
            $product = Product::query()->firstOrCreate(['slug' => $productSlug], [
                'organisation_id' => $organisation->id,
                'name' => 'Policy Point',
                'status' => 'active',
                'product_type' => 'newsletter',
                'primary_collection_handle' => 'foundation_newsletters',
                'blueprint_handle' => 'policy_point',
                'default_template_family' => 'policy_point',
                'domain_status' => 'unconfigured',
                'fallback_to_platform_domain' => true,
            ]);
            $group = SubscriberGroup::query()->firstOrCreate(['slug' => $groupSlug], [
                'organisation_id' => $organisation->id,
                'product_id' => $product->id,
                'audience_scope' => 'product_public',
                'name' => 'Policy Point Subscribers',
                'collection_handle' => 'foundation_newsletters',
                'description' => 'Audience group for Dataphyte Foundation / Policy Point.',
                'is_public_preference' => true,
            ]);
            SubscriberSubGroup::query()->firstOrCreate(
                ['subscriber_group_id' => $group->id, 'slug' => 'newsletter'],
                ['name' => 'Newsletter', 'purpose' => 'preference'],
            );
            $engagement->ensureSegments($group);
        });

        $this->info('Foundation Policy Point product and public audience are provisioned. No memberships were migrated.');

        return self::SUCCESS;
    }
}
