<?php

namespace Tests\Feature;

use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\Organisation;
use App\Models\OrganisationUserScope;
use App\Models\Product;
use App\Models\SubscriberGroup;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\Authorize;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class OrganisationProfileTest extends TestCase
{
    public function test_org_operator_can_publish_only_owned_groups_and_save_public_profile(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $this->withoutMiddleware(Authorize::class);
        [$owned, $ownedGroup] = $this->organisationWithGroup('Insight', 'insight_newsletters');
        [$foreign, $foreignGroup] = $this->organisationWithGroup('Foundation', 'foundation_newsletters');
        Storage::fake('public');
        $user = StatamicUser::make()->id((string) Str::uuid())->email('profile-'.Str::random(8).'@example.test');
        $user->assignRole('scoped_operator');
        $user->save();

        try {
            $identity = app(StatamicUserIdentityBridgeInterface::class)->provision($user);
            OrganisationUserScope::query()->create([
                'user_id' => $identity->id, 'organisation_id' => $owned->id,
                'scope_role' => 'organisation_operator', 'status' => 'active',
            ]);
            $this->actingAs($user, config('statamic.users.guards.cp', 'web'));

            $this->get(cp_route('organisation-profiles.index'))
                ->assertOk()->assertSee($ownedGroup->name)->assertDontSee($foreignGroup->name);
            $this->put(cp_route('organisation-profiles.update', $foreign), ['name' => 'Changed'])
                ->assertForbidden();
            $this->put(cp_route('organisation-profiles.update', $owned), [
                'name' => 'Dataphyte Insight', 'website_url' => 'https://insight.example.test',
                'brand_primary_color' => '#123456', 'brand_secondary_color' => '#ABCDEF',
                'public_group_ids' => [$foreignGroup->id],
            ])->assertSessionHasErrors('public_group_ids');

            $this->put(cp_route('organisation-profiles.update', $owned), [
                'name' => 'Dataphyte Insight', 'website_url' => 'https://insight.example.test',
                'brand_primary_color' => '#123456', 'brand_secondary_color' => '#ABCDEF',
                'addresses' => [
                    ['label' => 'Nigeria', 'address' => "1 Insight Road\nLagos"],
                    ['label' => 'UK', 'address' => '34 Example Road, London'],
                ],
                'social_links' => ['linkedin' => 'https://linkedin.example.test/insight'],
                'unsubscribe_message' => 'Choose the updates you still want.',
                'logo' => UploadedFile::fake()->image('logo.png'),
                'public_group_ids' => [$ownedGroup->id],
            ])->assertRedirect();
            $this->assertSame('Dataphyte Insight', $owned->fresh()->name);
            $this->assertSame('Choose the updates you still want.', $owned->fresh()->unsubscribe_message);
            $this->assertSame('UK', $owned->fresh()->publicAddresses()[1]['label']);
            $this->assertSame("1 Insight Road\nLagos", $owned->fresh()->postal_address);
            Storage::disk('public')->assertExists($owned->fresh()->logo_path);
            $this->assertTrue($ownedGroup->fresh()->is_public_preference);
            $this->assertFalse($foreignGroup->fresh()->is_public_preference);
        } finally {
            $user->delete();
        }
    }

    public function test_legacy_postal_address_remains_visible_until_structured_addresses_are_saved(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Legacy', 'slug' => 'legacy', 'status' => 'active',
            'postal_address' => 'Old address',
        ]);

        $this->assertSame([['label' => 'Main office', 'address' => 'Old address']], $organisation->publicAddresses());
        $organisation->update(['addresses' => []]);
        $this->assertSame([], $organisation->fresh()->publicAddresses());
    }

    private function organisationWithGroup(string $name, string $handle): array
    {
        $organisation = Organisation::query()->create([
            'name' => $name, 'slug' => strtolower($name), 'status' => 'active',
            'primary_collection_handle' => $handle,
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => $name.' Product',
            'slug' => strtolower($name).'-product', 'status' => 'active',
            'product_type' => 'newsletter', 'primary_collection_handle' => $handle,
        ]);
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'collection_handle' => $handle, 'name' => $name.' Audience',
        ]);

        return [$organisation, $group];
    }
}
