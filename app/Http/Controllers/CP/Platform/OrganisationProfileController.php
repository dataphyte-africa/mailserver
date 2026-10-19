<?php

namespace App\Http\Controllers\CP\Platform;

use App\Models\Organisation;
use App\Models\SubscriberGroup;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganisationProfileController
{
    public function __construct(private readonly CpOperatorScope $scope) {}

    public function index(Request $request): View
    {
        $organisations = Organisation::query()
            ->whereKey($this->scope->organisationIds($request->user()))
            ->where('status', 'active')
            ->with(['subscriberGroups' => fn ($query) => $query
                ->whereNull('archived_at')
                ->whereHas('product', fn ($product) => $product
                    ->where('status', 'active')
                    ->whereColumn('products.organisation_id', 'subscriber_groups.organisation_id')
                    ->whereColumn('products.primary_collection_handle', 'subscriber_groups.collection_handle'))
                ->with('product')
                ->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('platform.cp.organisation-profiles.index', compact('organisations'));
    }

    public function update(Request $request, Organisation $organisation): RedirectResponse
    {
        abort_unless(
            $organisation->status === 'active'
                && in_array((int) $organisation->getKey(), $this->scope->organisationIds($request->user()), true),
            403,
            'Organisation is outside your active scope.'
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'addresses' => ['nullable', 'array', 'max:10'],
            'addresses.*.label' => ['nullable', 'string', 'max:100'],
            'addresses.*.address' => ['nullable', 'string', 'max:1000'],
            'website_url' => ['nullable', 'url', 'starts_with:https://', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'social_links.facebook' => ['nullable', 'url', 'starts_with:https://', 'max:255'],
            'social_links.instagram' => ['nullable', 'url', 'starts_with:https://', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url', 'starts_with:https://', 'max:255'],
            'social_links.x' => ['nullable', 'url', 'starts_with:https://', 'max:255'],
            'social_links.youtube' => ['nullable', 'url', 'starts_with:https://', 'max:255'],
            'brand_primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_secondary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'unsubscribe_message' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'public_group_ids' => ['nullable', 'array'],
            'public_group_ids.*' => ['integer', 'distinct'],
        ]);

        $eligible = SubscriberGroup::query()
            ->where('organisation_id', $organisation->getKey())
            ->whereNull('archived_at')
            ->whereHas('product', fn ($product) => $product
                ->where('status', 'active')
                ->whereColumn('products.organisation_id', 'subscriber_groups.organisation_id')
                ->whereColumn('products.primary_collection_handle', 'subscriber_groups.collection_handle'))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selected = array_map('intval', $validated['public_group_ids'] ?? []);

        if (array_diff($selected, $eligible) !== []) {
            throw ValidationException::withMessages([
                'public_group_ids' => 'Only active audience groups in this organisation can be published.',
            ]);
        }

        $addresses = [];
        foreach ($validated['addresses'] ?? [] as $index => $row) {
            $label = trim($row['label'] ?? '');
            $address = trim($row['address'] ?? '');
            if ($label === '' && $address === '') {
                continue;
            }
            if ($label === '' || $address === '') {
                throw ValidationException::withMessages([
                    'addresses.'.$index => 'Each address needs both a label and an address.',
                ]);
            }
            $addresses[] = compact('label', 'address');
        }

        $attributes = collect($validated)->only([
            'name', 'website_url', 'brand_primary_color',
            'brand_secondary_color', 'unsubscribe_message',
        ])->all();
        $attributes['addresses'] = $addresses;
        $attributes['postal_address'] = $addresses[0]['address'] ?? null;
        $attributes['social_links'] = array_filter(
            array_intersect_key($validated['social_links'] ?? [], array_flip(['facebook', 'instagram', 'linkedin', 'x', 'youtube']))
        );

        foreach (['logo', 'banner'] as $image) {
            if ($request->hasFile($image)) {
                $attributes[$image.'_path'] = $request->file($image)
                    ->store('organisation-branding/'.$organisation->getKey(), 'public');
            }
        }

        DB::transaction(function () use ($organisation, $attributes, $selected): void {
            $organisation->update($attributes);
            SubscriberGroup::query()->where('organisation_id', $organisation->getKey())
                ->where('is_public_preference', true)
                ->update(['is_public_preference' => false]);
            SubscriberGroup::query()->where('organisation_id', $organisation->getKey())
                ->whereKey($selected)
                ->update(['is_public_preference' => true]);
        });

        return back()->with('success', $organisation->name.' profile saved.');
    }
}
