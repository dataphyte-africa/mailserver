<?php

namespace App\Providers;

use App\Console\Commands\Platform\ProvisionStatamicUser;
use App\Contracts\Analytics\AnalyticsEventStoreInterface;
use App\Contracts\Analytics\AnalyticsReaderInterface;
use App\Contracts\Analytics\AnalyticsWriterInterface;
use App\Contracts\Authorization\PermissionRegistryInterface;
use App\Contracts\Authorization\ScopeResolverInterface;
use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Contracts\Domain\DomainResolverInterface;
use App\Contracts\Domain\ProductUrlGeneratorInterface;
use App\Contracts\Domain\RequestContextResolverInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Statamic\Hooks\CP\EntriesIndexQuery;
use Statamic\Events\EntrySaving;
use Statamic\Facades\CP\Nav;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerPlatformContracts();
        $this->registerAuthorizationContracts();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::pushMiddlewareToGroup('statamic.cp.authenticated', \App\Http\Middleware\EnforceCpCollectionOwnership::class);
        Gate::policy(\Statamic\Contracts\Entries\Collection::class, \App\Policies\ScopedCollectionPolicy::class);
        Gate::policy(\Statamic\Contracts\Entries\Entry::class, \App\Policies\ScopedEntryPolicy::class);

        EntriesIndexQuery::hook('query', function ($payload, $next) {
            $scope = app(\App\Services\Platform\CpOperatorScope::class);
            $handles = $scope->blueprintHandlesForCollection(request()->user(), $payload->collection->handle());
            if ($handles !== null) {
                $payload->query->whereIn('blueprint', $handles);
            }
            return $next($payload);
        });

        Event::listen(EntrySaving::class, function (EntrySaving $event): void {
            if (! request()->is('cp/*')) {
                return;
            }
            abort_unless(app(\App\Services\Platform\CpOperatorScope::class)->canEntry(
                request()->user(), $event->entry->collectionHandle(), $event->entry->blueprint()?->handle(),
            ), 403);
        });

        Nav::extend(function ($nav): void {
            if (\Statamic\Facades\User::current()?->isSuper()) {
                return;
            }
            foreach ([
                'Content' => ['Collections', 'Navigation', 'Taxonomies', 'Assets', 'Globals'],
                'Fields' => ['Blueprints', 'Fieldsets'],
                'Tools' => ['Forms', 'Updates', 'Addons', 'Utilities'],
                'Settings' => ['Site'],
                'Users' => ['Users', 'Groups', 'Permissions'],
            ] as $section => $items) {
                foreach ($items as $item) {
                    $nav->remove($section, $item);
                }
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProvisionStatamicUser::class,
            ]);
        }
    }

    protected function registerPlatformContracts(): void
    {
        $this->app->bind(AnalyticsReaderInterface::class, function ($app) {
            $driver = (string) config('platform.analytics.driver', 'database');
            $class = (string) config("platform.analytics.drivers.{$driver}.reader");

            return $app->make($class);
        });

        $this->app->bind(AnalyticsWriterInterface::class, function ($app) {
            $driver = (string) config('platform.analytics.driver', 'database');
            $class = (string) config("platform.analytics.drivers.{$driver}.writer");

            return $app->make($class);
        });

        $this->app->singleton(AnalyticsEventStoreInterface::class, function ($app) {
            $class = (string) config('platform.analytics.event_store');

            return $app->make($class);
        });

        $this->app->singleton(DomainResolverInterface::class, function ($app) {
            $class = (string) config('platform.domain.resolver');

            return $app->make($class);
        });

        $this->app->singleton(ProductUrlGeneratorInterface::class, function ($app) {
            $class = (string) config('platform.domain.url_generator');

            return $app->make($class);
        });

        $this->app->singleton(RequestContextResolverInterface::class, function ($app) {
            $class = (string) config('platform.domain.request_context_resolver');

            return $app->make($class);
        });
    }

    protected function registerAuthorizationContracts(): void
    {
        $this->app->singleton(PermissionRegistryInterface::class, function ($app) {
            $class = (string) config('platform.authorization.permission_registry');

            return $app->make($class);
        });

        $this->app->singleton(ScopeResolverInterface::class, function ($app) {
            $class = (string) config('platform.authorization.scope_resolver');

            return $app->make($class);
        });

        $this->app->singleton(StatamicUserIdentityBridgeInterface::class, function ($app) {
            $class = (string) config('platform.authorization.statamic_user_identity_bridge');

            return $app->make($class);
        });
    }
}
