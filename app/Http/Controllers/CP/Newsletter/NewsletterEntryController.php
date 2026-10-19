<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Models\EntryModel;
use App\Models\Product;
use App\Services\Newsletter\NewsletterEntryProduct;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NewsletterEntryController
{
    public function create(Request $request, CpOperatorScope $scope): View
    {
        return view('newsletter.cp.entries.create', [
            'products' => $this->products($request, $scope),
        ]);
    }

    public function index(Request $request, CpOperatorScope $scope): View
    {
        $products = $this->products($request, $scope);
        $pairs = $products->keyBy(fn (Product $product) => $product->primary_collection_handle.'|'.$product->blueprint_handle);

        $entries = EntryModel::query()
            ->where(function ($query) use ($products): void {
                if ($products->isEmpty()) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                foreach ($products as $product) {
                    $query->orWhere(fn ($query) => $query
                        ->where('collection', $product->primary_collection_handle)
                        ->where('blueprint', $product->blueprint_handle));
                }
            })
            ->when($request->filled('product'), fn ($query) => $query->where(function ($query) use ($request, $products): void {
                $product = $products->firstWhere('id', (int) $request->input('product'));
                $product
                    ? $query->where('collection', $product->primary_collection_handle)->where('blueprint', $product->blueprint_handle)
                    : $query->whereRaw('1 = 0');
            }))
            ->orderByDesc('date')->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('newsletter.cp.entries.index', compact('entries', 'products', 'pairs'));
    }

    private function products(Request $request, CpOperatorScope $scope)
    {
        return Product::query()->whereKey($scope->productIds($request->user()))
            ->whereNotNull('primary_collection_handle')->whereNotNull('blueprint_handle')
            ->with('organisation')->orderBy('name')->get()
            ->filter(fn (Product $product) => $product->organisation?->status === 'active'
                && app(NewsletterEntryProduct::class)->find($product->primary_collection_handle, $product->blueprint_handle)?->is($product))
            ->values();
    }
}
