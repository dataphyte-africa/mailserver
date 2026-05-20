<?php

namespace App\Console\Commands\Newsletter;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Facades\File;
use Statamic\Facades\Form;
use Statamic\Facades\YAML;
use Statamic\Forms\Form as StacheForm;
use Statamic\Forms\FormRepository as StacheFormRepository;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class ImportYamlFiles extends Command
{
    protected $signature = 'newsletter:import-yaml-files
        {paths* : One or more YAML files under resources/blueprints or resources/forms}';

    protected $description = 'Import specific blueprint or form YAML files into the database.';

    public function handle(): int
    {
        $rows = [];

        foreach ((array) $this->argument('paths') as $inputPath) {
            $path = $this->resolvePath($inputPath);

            if (! $path || ! is_file($path)) {
                $this->error("File not found: {$inputPath}");

                return self::FAILURE;
            }

            $normalized = str_replace('\\', '/', $path);

            if ($this->isBlueprintPath($normalized)) {
                $rows[] = ['blueprint', $this->importBlueprint($normalized)];
                continue;
            }

            if ($this->isFormPath($normalized)) {
                $rows[] = ['form', $this->importForm($normalized)];
                continue;
            }

            $this->error("Unsupported YAML path: {$inputPath}");
            $this->line('Supported roots: resources/blueprints/, resources/forms/');

            return self::FAILURE;
        }

        $this->table(['Type', 'Imported'], $rows);
        $this->info('Selected YAML files imported successfully.');

        return self::SUCCESS;
    }

    private function importBlueprint(string $path): string
    {
        $directory = str_replace('\\', '/', resource_path('blueprints'));
        $relative = Str::after(Str::before($path, '.yaml'), $directory.'/');
        [$namespace, $handle] = $this->getBlueprintNamespaceAndHandle($relative);

        $contents = YAML::file($path)->parse();
        $contents = $this->normalizeBlueprintTabOrder($contents);

        $blueprint = \Statamic\Facades\Blueprint::make()
            ->setHidden(Arr::pull($contents, 'hide'))
            ->setOrder(Arr::pull($contents, 'order'))
            ->setInitialPath($path)
            ->setHandle($handle)
            ->setNamespace($namespace)
            ->setContents($contents);

        $lastModified = Carbon::createFromTimestamp(File::lastModified($path));

        app('statamic.eloquent.blueprints.model')::firstOrNew([
            'handle' => $blueprint->handle(),
            'namespace' => $blueprint->namespace() ?? null,
        ])->fill([
            'data' => $blueprint->contents(),
            'created_at' => $lastModified,
            'updated_at' => $lastModified,
        ])->save();

        return ($namespace ? $namespace.'.' : '').$handle;
    }

    private function importForm(string $path): string
    {
        $this->useDefaultFormRepositories();

        $handle = pathinfo($path, PATHINFO_FILENAME);
        $form = Form::find($handle);

        if (! $form) {
            throw new \RuntimeException("Form YAML could not be resolved for handle [{$handle}] at [{$path}].");
        }

        $lastModified = Carbon::createFromTimestamp(File::lastModified($path));

        \Statamic\Eloquent\Forms\Form::makeModelFromContract($form)
            ->fill([
                'created_at' => $lastModified,
                'updated_at' => $lastModified,
            ])
            ->save();

        return $handle;
    }

    private function useDefaultFormRepositories(): void
    {
        Facade::clearResolvedInstance(FormContract::class);
        Facade::clearResolvedInstance(FormRepositoryContract::class);

        app()->bind(FormContract::class, StacheForm::class);
        app()->bind(FormRepositoryContract::class, StacheFormRepository::class);
    }

    private function resolvePath(string $path): ?string
    {
        if (str_starts_with($path, '/')) {
            return realpath($path) ?: $path;
        }

        $candidates = [
            base_path($path),
            resource_path($path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        return null;
    }

    private function isBlueprintPath(string $path): bool
    {
        $root = str_replace('\\', '/', resource_path('blueprints')).'/';

        return str_starts_with($path, $root);
    }

    private function isFormPath(string $path): bool
    {
        $root = str_replace('\\', '/', resource_path('forms')).'/';

        return str_starts_with($path, $root);
    }

    private function getBlueprintNamespaceAndHandle(string $blueprint): array
    {
        $blueprint = str_replace('/', '.', $blueprint);
        $parts = explode('.', $blueprint);
        $handle = array_pop($parts);
        $namespace = implode('.', $parts);

        return [empty($namespace) ? null : $namespace, $handle];
    }

    private function normalizeBlueprintTabOrder(array $contents): array
    {
        if (! isset($contents['tabs']) || ! is_array($contents['tabs'])) {
            return $contents;
        }

        $count = 0;
        $contents['tabs'] = collect($contents['tabs'])
            ->map(function ($tab) use (&$count) {
                $tab['__count'] = $count++;

                if (isset($tab['sections']) && is_array($tab['sections'])) {
                    $sectionCount = 0;
                    $tab['sections'] = collect($tab['sections'])
                        ->map(function ($section) use (&$sectionCount) {
                            $section['__count'] = $sectionCount++;

                            return $section;
                        })
                        ->toArray();
                }

                return $tab;
            })
            ->toArray();

        return $contents;
    }
}
