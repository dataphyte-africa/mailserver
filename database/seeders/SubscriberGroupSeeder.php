<?php

namespace Database\Seeders;

use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Database\Seeder;

class SubscriberGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name'        => 'Insight Subscribers',
                'slug'        => 'insight-subscribers',
                'collection_handle' => 'insight_newsletters',
                'description' => 'Dataphyte Insight newsletter subscribers',
                'sub_groups'  => [
                    ['name' => 'Pocket Science', 'slug' => 'pocket-science'],
                    ['name' => 'SenorRita', 'slug' => 'senorrita'],
                    ['name' => 'Marina and Maitama', 'slug' => 'marina-maitama'],
                    ['name' => 'Data Dive', 'slug' => 'data-dive'],
                ],
            ],
            [
                'name'        => 'Foundation',
                'slug'        => 'foundation',
                'collection_handle' => 'foundation_newsletters',
                'description' => 'Dataphyte Foundation newsletter subscribers',
                'sub_groups'  => [
                    ['name' => 'Weekly',     'slug' => 'weekly'],
                    ['name' => 'Activities', 'slug' => 'activities'],
                ],
            ],
            [
                'name'        => 'Policy Point',
                'slug'        => 'policy-point',
                'collection_handle' => 'policy_point_newsletters',
                'description' => 'Policy Point newsletter subscribers',
                'sub_groups'  => [
                    ['name' => 'As Frequently', 'slug' => 'as-frequently'],
                    ['name' => 'Monthly',       'slug' => 'monthly'],
                ],
            ],
        ];

        foreach ($groups as $groupData) {
            $subGroups = $groupData['sub_groups'];
            unset($groupData['sub_groups']);

            $group = SubscriberGroup::firstOrCreate(
                ['slug' => $groupData['slug']],
                $groupData
            );

            foreach ($subGroups as $subGroup) {
                SubscriberSubGroup::firstOrCreate(
                    [
                        'subscriber_group_id' => $group->id,
                        'slug'                => $subGroup['slug'],
                    ],
                    ['name' => $subGroup['name']]
                );
            }
        }

        $this->command->info('Subscriber groups and sub-groups seeded.');
    }
}
