<?php

namespace App\Services\Platform;

use App\Models\Organisation;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class StatamicNewsletterProductSyncService
{
    /**
     * @return array<string, mixed>
     */
    public function sync(bool $dryRun = false): array
    {
        $results = [
            'dry_run' => $dryRun,
            'organisations' => [],
            'products' => [],
        ];

        foreach ($this->configuredNewsletterCollections() as $collectionHandle => $collectionConfig) {
            $organisationAttributes = $this->organisationAttributes($collectionHandle, $collectionConfig);
            $organisation = Organisation::query()->where('slug', $organisationAttributes['slug'])->first();

            $results['organisations'][] = [
                'handle' => $collectionHandle,
                'name' => $organisationAttributes['name'],
                'action' => $organisation ? 'update' : 'create',
            ];

            if (! $dryRun) {
                $organisation = Organisation::query()->updateOrCreate(
                    ['slug' => $organisationAttributes['slug']],
                    $organisationAttributes,
                );
            }

            foreach ($this->blueprintsForCollection($collectionHandle) as $blueprint) {
                $productAttributes = $this->productAttributes(
                    $collectionHandle,
                    $blueprint['handle'],
                    $blueprint['title'],
                    $organisation,
                );

                $existingProduct = Product::query()->where('slug', $productAttributes['slug'])->first();
                $results['products'][] = [
                    'collection_handle' => $collectionHandle,
                    'blueprint_handle' => $blueprint['handle'],
                    'name' => $productAttributes['name'],
                    'action' => $existingProduct ? 'update' : 'create',
                ];

                if (! $dryRun && $organisation) {
                    Product::query()->updateOrCreate(
                        ['slug' => $productAttributes['slug']],
                        $productAttributes,
                    );
                }
            }
        }

        return $results;
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    protected function configuredNewsletterCollections(): Collection
    {
        return collect(config('newsletter.collections', []))
            ->filter(fn ($config, $handle): bool => is_string($handle) && is_array($config));
    }

    /**
     * @param  array<string, mixed>  $collectionConfig
     * @return array<string, mixed>
     */
    protected function organisationAttributes(string $collectionHandle, array $collectionConfig): array
    {
        return [
            'name' => (string) ($collectionConfig['label'] ?? Str::headline($collectionHandle)),
            'slug' => Str::slug($collectionHandle),
            'status' => 'active',
            'primary_collection_handle' => $collectionHandle,
            'default_domain' => null,
            'default_mail_domain' => null,
            'default_from_name' => $collectionConfig['from_name'] ?? null,
            'default_reply_to' => filled($collectionConfig['reply_to'] ?? null) ? $collectionConfig['reply_to'] : null,
            'support_contact' => filled($collectionConfig['from_email'] ?? null) ? $collectionConfig['from_email'] : null,
        ];
    }

    /**
     * @return array<int, array{handle: string, title: string}>
     */
    protected function blueprintsForCollection(string $collectionHandle): array
    {
        $path = resource_path("blueprints/collections/{$collectionHandle}");

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->filter(fn ($file): bool => $file->getExtension() === 'yaml')
            ->map(function ($file): array {
                $handle = Str::beforeLast($file->getFilename(), '.yaml');
                $data = Yaml::parseFile($file->getPathname());

                return [
                    'handle' => $handle,
                    'title' => is_array($data) && filled($data['title'] ?? null)
                        ? (string) $data['title']
                        : Str::headline($handle),
                ];
            })
            ->sortBy('title')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function productAttributes(
        string $collectionHandle,
        string $blueprintHandle,
        string $title,
        ?Organisation $organisation,
    ): array {
        return [
            'organisation_id' => $organisation?->getKey(),
            'name' => $title,
            'slug' => Str::slug($collectionHandle.'-'.$blueprintHandle),
            'status' => 'active',
            'product_type' => 'newsletter',
            'public_domain' => null,
            'mail_from_domain' => null,
            'forms_domain' => null,
            'domain_status' => 'unconfigured',
            'domain_verified_at' => null,
            'domain_is_primary' => false,
            'primary_collection_handle' => $collectionHandle,
            'blueprint_handle' => $blueprintHandle,
            'default_sender_profile' => null,
            'default_template_family' => $blueprintHandle,
            'fallback_to_platform_domain' => true,
        ];
    }
}
