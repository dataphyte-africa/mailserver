<?php

namespace Tests\Unit;

use App\Contracts\Analytics\AnalyticsEventStoreInterface;
use App\Contracts\Analytics\AnalyticsReaderInterface;
use App\Contracts\Analytics\AnalyticsWriterInterface;
use App\Contracts\Domain\DomainResolverInterface;
use App\Contracts\Domain\ProductUrlGeneratorInterface;
use App\Contracts\Domain\RequestContextResolverInterface;
use App\Support\Platform\Analytics\DatabaseAnalyticsReader;
use App\Support\Platform\Analytics\DatabaseAnalyticsWriter;
use App\Support\Platform\Analytics\NullAnalyticsEventStore;
use App\Support\Platform\Analytics\UnavailableAnalyticsReader;
use App\Support\Platform\Analytics\UnavailableAnalyticsWriter;
use App\Support\Platform\Domain\DomainResolver;
use App\Support\Platform\Domain\ProductUrlGenerator;
use App\Support\Platform\Domain\RequestContextResolver;
use Tests\TestCase;

class PlatformContractsTest extends TestCase
{
    public function test_database_analytics_driver_is_the_default(): void
    {
        $this->assertSame('database', config('platform.analytics.driver'));
        $this->assertInstanceOf(DatabaseAnalyticsReader::class, app(AnalyticsReaderInterface::class));
        $this->assertInstanceOf(DatabaseAnalyticsWriter::class, app(AnalyticsWriterInterface::class));
    }

    public function test_clickhouse_driver_resolves_to_unavailable_placeholders_until_future_track_is_started(): void
    {
        config()->set('platform.analytics.driver', 'clickhouse');

        $this->assertInstanceOf(UnavailableAnalyticsReader::class, app(AnalyticsReaderInterface::class));
        $this->assertInstanceOf(UnavailableAnalyticsWriter::class, app(AnalyticsWriterInterface::class));
    }

    public function test_platform_event_store_binding_uses_the_null_store_by_default(): void
    {
        $this->assertInstanceOf(NullAnalyticsEventStore::class, app(AnalyticsEventStoreInterface::class));
    }

    public function test_domain_contracts_resolve_to_shared_platform_scaffolds(): void
    {
        $this->assertInstanceOf(DomainResolver::class, app(DomainResolverInterface::class));
        $this->assertInstanceOf(ProductUrlGenerator::class, app(ProductUrlGeneratorInterface::class));
        $this->assertInstanceOf(RequestContextResolver::class, app(RequestContextResolverInterface::class));
    }

    public function test_domain_resolver_can_evaluate_verification_and_enabled_state_without_feature_logic(): void
    {
        /** @var DomainResolver $resolver */
        $resolver = app(DomainResolverInterface::class);

        $this->assertTrue($resolver->isVerified(['status' => 'verified']));
        $this->assertFalse($resolver->isVerified(['status' => 'pending_verification']));
        $this->assertTrue($resolver->isEnabled(['enabled' => true]));
        $this->assertFalse($resolver->isEnabled(['disabled' => true]));
    }
}
