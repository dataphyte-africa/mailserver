<?php

namespace App\Http\Controllers\CP\Platform;

use App\Models\Product;
use App\Services\Newsletter\NewsletterBanner;
use App\Services\Newsletter\NewsletterEntryProduct;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductBannerController
{
    public function __construct(private readonly CpOperatorScope $scope) {}

    public function index(Request $request): View
    {
        $products = Product::query()->whereKey($this->scope->productIds($request->user()))
            ->where('status', 'active')->with('organisation')->orderBy('name')->get();

        return view('platform.cp.product-banners.index', compact('products'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProduct($request, $product);
        $validated = $request->validate([
            'banner' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'remove_banner' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('banner')) {
            $oldPath = $product->banner_path;
            $product->update(['banner_path' => $request->file('banner')->store('product-branding/'.$product->id, 'public')]);
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        } elseif ($validated['remove_banner'] ?? false) {
            $oldPath = $product->banner_path;
            $product->update(['banner_path' => null]);
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return back()->with('success', $product->name.' banner saved.');
    }

    public function default(Request $request, NewsletterEntryProduct $entryProduct, NewsletterBanner $banner): JsonResponse
    {
        $input = $request->validate([
            'collection' => ['required', 'string', 'max:100'],
            'blueprint' => ['required', 'string', 'max:100'],
        ]);
        $product = $entryProduct->find($input['collection'], $input['blueprint']);
        abort_unless($product && in_array((int) $product->id, $this->scope->productIds($request->user()), true), 403);
        $product = $product->fresh('organisation');

        return response()->json([
            'url' => $banner->defaultUrl($product, $product->organisation),
            'source' => $product->banner_path ? 'product' : 'organisation',
            'product' => $product->name,
        ]);
    }

    public function uploadOverride(Request $request, NewsletterEntryProduct $entryProduct): JsonResponse
    {
        $input = $request->validate([
            'collection' => ['required', 'string', 'max:100'],
            'blueprint' => ['required', 'string', 'max:100'],
            'banner' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);
        $product = $entryProduct->find($input['collection'], $input['blueprint']);
        abort_unless($product && in_array((int) $product->id, $this->scope->productIds($request->user()), true), 403);

        $file = $request->file('banner');
        $path = $file->storeAs(
            'product-branding/'.$product->id.'/newsletter-overrides',
            Str::uuid().'.'.$file->extension(),
            'public',
        );

        return response()->json(['url' => url(Storage::disk('public')->url($path))]);
    }

    private function authorizeProduct(Request $request, Product $product): void
    {
        abort_unless($product->status === 'active'
            && in_array((int) $product->id, $this->scope->productIds($request->user()), true), 403);
    }
}
