<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\CollectionRegistry;
use App\Support\Platform\Ownership\SubscriberGroupOwnershipWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubscriberGroupSeeder extends Seeder
{
    public function run(SubscriberGroupOwnershipWriter $groups): void
    {
        $newsletterCollections = array_keys(app(CollectionRegistry::class)->options());
        $seeded = 0;

        Product::query()
            ->with('organisation')
            ->where('status', 'active')
            ->whereHas('organisation', fn ($query) => $query->where('status', 'active'))
            ->whereIn('primary_collection_handle', $newsletterCollections)
            ->orderBy('primary_collection_handle')
            ->orderBy('name')
            ->get()
            ->each(function (Product $product) use ($groups, &$seeded): void {
                $groupName = "{$product->name} Subscribers";
                $slug = Str::slug($groupName);

                $group = SubscriberGroup::query()->firstOrNew(['slug' => $slug]);

                $groups->updateForProduct($product, $group, [
                    'name' => $groupName,
                    'slug' => $slug,
                    'collection_handle' => $product->primary_collection_handle,
                    'description' => "Audience group for {$product->organisation?->name} / {$product->name}.",
                    'archived_at' => null,
                    'archived_by' => null,
                ]);

                foreach ($this->subGroupsForProduct($product) as $subGroup) {
                    SubscriberSubGroup::updateOrCreate(
                        [
                            'subscriber_group_id' => $group->id,
                            'slug' => $subGroup['slug'],
                        ],
                        [
                            'name' => $subGroup['name'],
                            'description' => $subGroup['description'],
                            'archived_at' => null,
                            'archived_by' => null,
                        ],
                    );
                }

                $seeded++;
            });

        $this->command->info("Product-owned subscriber groups seeded: {$seeded}.");
    }

    /**
     * @return array<int, array{name: string, slug: string, description: string}>
     */
    private function subGroupsForProduct(Product $product): array
    {
        $base = [
            [
                'name' => 'Regular',
                'slug' => 'regular',
                'description' => 'Default audience list for confirmed subscribers.',
            ],
            [
                'name' => 'Priority Updates',
                'slug' => 'priority-updates',
                'description' => 'Subscribers who should receive urgent or high-priority updates.',
            ],
        ];

        $custom = match ($product->blueprint_handle) {
            'activities', 'project_update' => [
                [
                    'name' => 'Events and Applications',
                    'slug' => 'events-applications',
                    'description' => 'Subscribers interested in events, opportunities, forms, or applications.',
                ],
            ],
            'policy_point' => [
                [
                    'name' => 'Monthly',
                    'slug' => 'monthly',
                    'description' => 'Subscribers who prefer monthly Policy Point updates.',
                ],
                [
                    'name' => 'As Frequently',
                    'slug' => 'as-frequently',
                    'description' => 'Subscribers who prefer each Policy Point update as it is published.',
                ],
            ],
            default => [
                [
                    'name' => 'Newsletter',
                    'slug' => 'newsletter',
                    'description' => 'Standard newsletter audience segment.',
                ],
            ],
        };

        return array_merge($base, $custom);
    }
}
