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
