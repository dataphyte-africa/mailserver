<?php

namespace App\Services\Forms;

use App\Contracts\Authorization\ScopeResolverInterface;
use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Statamic\Contracts\Auth\User as StatamicUser;

class ScopedProductFormProductSelector
{
    public function __construct(
        private readonly StatamicUserIdentityBridgeInterface $identityBridge,
        private readonly ScopeResolverInterface $scopes,
    ) {}

    /**
     * @return Collection<int, Product>
     */
    public function productsFor(?Authenticatable $operator): Collection
    {
        if ($operator instanceof StatamicUser && $operator->isSuper()) {
            return $this->activeProductsQuery()->get();
        }

        $user = $this->identityBridge->resolve($operator);

        if ($user === null) {
            return new Collection;
        }

        $productIds = $this->scopes->productIds($user);

        if ($productIds === []) {
            return new Collection;
        }

        return $this->activeProductsQuery()
            ->whereKey($productIds)
            ->get();
    }

    public function resolve(?Authenticatable $operator, int $productId): ?Product
    {
        if ($productId <= 0) {
            return null;
        }

        return $this->productsFor($operator)
            ->first(fn (Product $product) => (int) $product->getKey() === $productId);
    }

    /**
     * @return Collection<int, Organisation>
     */
    public function organisationsFor(?Authenticatable $operator): Collection
    {
        $products = $this->productsFor($operator)->loadMissing('organisation');

        return new Collection($products
            ->pluck('organisation')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all());
    }

    public function resolveOrganisation(?Authenticatable $operator, int $organisationId): ?Organisation
    {
        if ($organisationId <= 0) {
            return null;
        }

        return $this->organisationsFor($operator)
            ->first(fn (Organisation $organisation) => (int) $organisation->getKey() === $organisationId);
    }

    public function resolveForm(?Authenticatable $operator, ProductForm $form): ?Product
    {
        if (! $form->exists || $form->product_id === null || $form->organisation_id === null) {
            return null;
        }

        $product = $this->resolve($operator, (int) $form->product_id);

        if ($product === null || (int) $product->organisation_id !== (int) $form->organisation_id) {
            return null;
        }

        return $product;
    }

    public function canAccessForm(?Authenticatable $operator, ProductForm $form): bool
    {
        if ($form->form_scope === 'organisation') {
            return $this->resolveOrganisation($operator, (int) $form->organisation_id) instanceof Organisation;
        }

        return $this->resolveForm($operator, $form) instanceof Product;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    private function activeProductsQuery()
    {
        return Product::query()
            ->with('organisation')
            ->where('status', 'active')
            ->whereHas('organisation', fn ($query) => $query->where('status', 'active'))
            ->orderBy('name');
    }
}
