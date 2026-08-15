<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['statamic_user_id', 'name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organisationScopes(): HasMany
    {
        return $this->hasMany(OrganisationUserScope::class);
    }

    public function activeOrganisationScopes(): HasMany
    {
        return $this->organisationScopes()->where('status', $this->activeScopeStatus());
    }

    public function productScopes(): HasMany
    {
        return $this->hasMany(ProductUserScope::class);
    }

    public function activeProductScopes(): HasMany
    {
        return $this->productScopes()->where('status', $this->activeScopeStatus());
    }

    public function scopeWithActiveOrganisationScope(Builder $query, int $organisationId): Builder
    {
        return $query->whereHas('organisationScopes', function (Builder $scopeQuery) use ($organisationId) {
            $scopeQuery
                ->where('organisation_id', $organisationId)
                ->where('status', $this->activeScopeStatus());
        });
    }

    public function scopeWithActiveProductScope(Builder $query, int $productId): Builder
    {
        return $query->whereHas('productScopes', function (Builder $scopeQuery) use ($productId) {
            $scopeQuery
                ->where('product_id', $productId)
                ->where('status', $this->activeScopeStatus());
        });
    }

    protected function activeScopeStatus(): string
    {
        return (string) config('platform.authorization.scope_statuses.active', 'active');
    }
}
