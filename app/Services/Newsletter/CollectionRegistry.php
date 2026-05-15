<?php

namespace App\Services\Newsletter;

use App\Models\SubscriberGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CollectionRegistry
{
    public function handles(): array
    {
        return array_keys(config('newsletter.collections', []));
    }

    public function validationRule(): string
    {
        return 'in:' . implode(',', $this->handles());
    }

    public function isKnown(?string $handle): bool
    {
        return $handle !== null && array_key_exists($handle, config('newsletter.collections', []));
    }

    public function config(string $handle): array
    {
        return config("newsletter.collections.{$handle}", []);
    }

    public function key(string $handle): string
    {
        return Str::of($handle)->replaceEnd('_newsletters', '')->toString();
    }

    public function label(?string $handle): string
    {
        if (! $handle) {
            return 'Unknown Collection';
        }

        $config = $this->config($handle);

        return Arr::get($config, 'label')
            ?? Arr::get($config, 'from_name')
            ?? Str::of($this->key($handle))->replace('_', ' ')->title()->toString();
    }

    public function shortLabel(?string $handle): string
    {
        if (! $handle) {
            return 'Unknown';
        }

        $config = $this->config($handle);

        return Arr::get($config, 'short_label')
            ?? Str::of($this->label($handle))->replaceStart('Dataphyte ', '')->toString();
    }

    public function groupName(string $handle): string
    {
        return Arr::get($this->config($handle), 'group_name', $this->shortLabel($handle));
    }

    public function groupSlug(string $handle): string
    {
        return Arr::get($this->config($handle), 'group_slug')
            ?? Str::of($this->key($handle))->replace('_', '-')->toString();
    }

    public function sender(?string $handle): array
    {
        $config = $handle ? $this->config($handle) : [];
        $fallback = config('newsletter.fallback', []);

        return [
            'from_email' => Arr::get($config, 'from_email', Arr::get($fallback, 'from_email')),
            'from_name' => Arr::get($config, 'from_name', Arr::get($fallback, 'from_name')),
            'reply_to' => Arr::get($config, 'reply_to', Arr::get($fallback, 'reply_to')),
            'brand_color' => Arr::get($config, 'brand_color', '#1a1a2e'),
        ];
    }

    public function formEndpoint(string $identifier): string
    {
        return route('newsletter.forms.submit', ['form' => $identifier]);
    }

    public function options(): array
    {
        return collect($this->handles())
            ->mapWithKeys(fn (string $handle) => [$handle => $this->label($handle)])
            ->all();
    }

    public function groupOptions(): array
    {
        if (! Schema::hasTable('subscriber_groups') || ! Schema::hasColumn('subscriber_groups', 'collection_handle')) {
            return [];
        }

        return SubscriberGroup::query()
            ->whereNotNull('collection_handle')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (SubscriberGroup $group) => [
                (string) $group->id => $group->name . ' (' . $this->shortLabel($group->collection_handle) . ')',
            ])
            ->all();
    }

    public function meta(): array
    {
        return collect($this->handles())
            ->mapWithKeys(fn (string $handle) => [
                $handle => [
                    'label'       => $this->label($handle),
                    'short_label' => $this->shortLabel($handle),
                    'group_name'  => $this->groupName($handle),
                    'group_slug'  => $this->groupSlug($handle),
                    'from_email'  => Arr::get($this->config($handle), 'from_email'),
                    'from_name'   => Arr::get($this->config($handle), 'from_name'),
                    'reply_to'    => Arr::get($this->config($handle), 'reply_to'),
                ],
            ])->all();
    }
}
