<?php

namespace Database\Factories;

use App\Models\SubscriberGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriberGroupFactory extends Factory
{
    protected $model = SubscriberGroup::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        return [
            'name'        => ucwords($name),
            'slug'        => Str::slug($name),
            'collection_handle' => 'foundation_newsletters',
            'description' => fake()->sentence(),
        ];
    }

    public function insight(): static
    {
        return $this->state([
            'name' => 'Insight Subscribers',
            'slug' => 'insight-subscribers',
            'collection_handle' => 'insight_newsletters',
        ]);
    }

    public function foundation(): static
    {
        return $this->state([
            'name' => 'Foundation',
            'slug' => 'foundation',
            'collection_handle' => 'foundation_newsletters',
        ]);
    }

    public function policyPoint(): static
    {
        return $this->state([
            'name' => 'Policy Point',
            'slug' => 'policy-point',
            'collection_handle' => 'policy_point_newsletters',
        ]);
    }
}
