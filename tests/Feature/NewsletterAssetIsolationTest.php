<?php

namespace Tests\Feature;

use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\Organisation;
use App\Models\OrganisationUserScope;
use App\Models\Product;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\User;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class NewsletterAssetIsolationTest extends TestCase
{
    public function test_operator_can_browse_only_the_active_organisations_image_container(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\Authorize::class);
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\AuthenticateSession::class);
        foreach (['newsletter_assets_foundation', 'newsletter_assets_insight', 'assets'] as $handle) {
            Storage::fake($handle);
            AssetContainer::make($handle)->title($handle)->disk($handle)->save();
        }

        $foundation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation-newsletters', 'status' => 'active',
        ]);
        $insight = Organisation::query()->create([
            'name' => 'Dataphyte Insight', 'slug' => 'insight-newsletters', 'status' => 'active',
        ]);
        foreach ([$foundation, $insight] as $organisation) {
            Product::query()->create([
                'organisation_id' => $organisation->id, 'name' => $organisation->name,
                'slug' => $organisation->slug, 'status' => 'active',
                'product_type' => 'newsletter', 'primary_collection_handle' => str_replace('-', '_', $organisation->slug),
                'blueprint_handle' => 'example',
            ]);
        }
        $operator = User::make()->id((string) Str::uuid())->email('asset-scope@example.test');
        $operator->assignRole('scoped_operator');
        $operator->save();

        try {
            $identity = app(StatamicUserIdentityBridgeInterface::class)->provision($operator);
            foreach ([$foundation, $insight] as $organisation) {
                OrganisationUserScope::query()->create([
                    'user_id' => $identity->id,
                    'organisation_id' => $organisation->id,
                    'scope_role' => 'organisation_operator',
                    'status' => 'active',
                ]);
            }

            $this->actingAs($operator, config('statamic.users.guards.cp', 'web'))
                ->withSession([CpOperatorScope::ACTIVE_ORGANISATION_SESSION_KEY => $foundation->id]);

            $this->get(cp_route('newsletter.groups.index'));
            $this->assertTrue($operator->can('view', AssetContainer::find('newsletter_assets_foundation')));
            $this->assertTrue($operator->can('store', [\Statamic\Contracts\Assets\Asset::class, AssetContainer::find('newsletter_assets_foundation')]));
            $this->assertFalse($operator->can('view', AssetContainer::find('newsletter_assets_insight')));
            $this->get('/cp/assets/browse/folders/newsletter_assets_foundation')->assertOk();
            $this->get('/cp/assets/browse/folders/newsletter_assets_insight')->assertForbidden();
            $this->get('/cp/assets/browse/folders/assets')->assertForbidden();
            $this->post('/cp/assets-fieldtype', ['assets' => ['newsletter_assets_insight::image.png']])->assertForbidden();
            $this->post('/cp/assets', [
                'container' => 'newsletter_assets_insight', 'folder' => '/',
                'file' => UploadedFile::fake()->image('foreign.png'),
            ])->assertForbidden();
            $this->post('/cp/assets', [
                'container' => 'newsletter_assets_foundation', 'folder' => '/',
                'file' => UploadedFile::fake()->image('owned.png'),
            ])->assertOk();
            Storage::disk('newsletter_assets_foundation')->assertExists('owned.png');

            $this->withSession([CpOperatorScope::ACTIVE_ORGANISATION_SESSION_KEY => $insight->id]);
            $this->get('/cp/assets/browse/folders/newsletter_assets_foundation')->assertForbidden();
            $this->get('/cp/assets/browse/folders/newsletter_assets_insight')->assertOk();
        } finally {
            $operator->delete();
        }
    }
}
