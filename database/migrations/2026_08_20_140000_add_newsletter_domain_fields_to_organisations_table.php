<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisations', function (Blueprint $table): void {
            $table->string('source_domain')->nullable()->after('default_domain');
            $table->string('newsletter_subdomain')->default('nl')->after('source_domain');
            $table->string('newsletter_domain')->nullable()->after('newsletter_subdomain');
            $table->string('newsletter_domain_status')->default('unconfigured')->after('newsletter_domain');
            $table->timestamp('newsletter_domain_verified_at')->nullable()->after('newsletter_domain_status');
            $table->string('newsletter_dns_record_type')->default('A')->after('newsletter_domain_verified_at');
            $table->string('newsletter_dns_expected_value')->nullable()->after('newsletter_dns_record_type');

            $table->index('newsletter_domain');
            $table->index('newsletter_domain_status');
        });
    }

    public function down(): void
    {
        Schema::table('organisations', function (Blueprint $table): void {
            $table->dropIndex(['newsletter_domain']);
            $table->dropIndex(['newsletter_domain_status']);
            $table->dropColumn([
                'source_domain',
                'newsletter_subdomain',
                'newsletter_domain',
                'newsletter_domain_status',
                'newsletter_domain_verified_at',
                'newsletter_dns_record_type',
                'newsletter_dns_expected_value',
            ]);
        });
    }
};
