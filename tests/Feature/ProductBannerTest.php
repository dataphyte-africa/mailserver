<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\Authorize;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class ProductBannerTest extends TestCase
{
    public function test_super_admin_can_upload_and_remove_product_default_banner(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $this->withoutMiddleware(Authorize::class);
        Storage::fake('public');
        $organisation = Organisation::query()->create([
            'name' => 'Insight', 'slug' => 'insight', 'status' => 'active',
            'banner_path' => 'organisation-branding/1/banner.png',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Marina and Maitama',
            'slug' => 'marina-maitama', 'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters', 'blueprint_handle' => 'marina_maitama',
        ]);
        $admin = StatamicUser::findByEmail('admin@mailserver.test');
        $this->actingAs($admin, config('statamic.users.guards.cp', 'web'));

        $this->get(cp_route('product-banners.index'))->assertOk()->assertSee($product->name);
        $this->getJson(cp_route('product-banners.default', [
            'collection' => 'insight_newsletters', 'blueprint' => 'marina_maitama',
        ]))->assertOk()->assertJsonPath('source', 'organisation');

        $this->put(cp_route('product-banners.update', $product), [
            'banner' => UploadedFile::fake()->image('banner.png'),
        ])->assertRedirect();
        Storage::disk('public')->assertExists($product->fresh()->banner_path);
        $this->getJson(cp_route('product-banners.default', [
            'collection' => 'insight_newsletters', 'blueprint' => 'marina_maitama',
        ]))->assertOk()->assertJsonPath('source', 'product');

        $override = $this->postJson(cp_route('product-banners.upload-override'), [
            'collection' => 'insight_newsletters', 'blueprint' => 'marina_maitama',
            'banner' => UploadedFile::fake()->image('issue.png'),
        ])->assertOk()->json('url');
        $this->assertStringContainsString('/product-branding/'.$product->id.'/newsletter-overrides/', $override);

        $this->put(cp_route('product-banners.update', $product), ['remove_banner' => true])->assertRedirect();
        $this->assertNull($product->fresh()->banner_path);
    }
}
