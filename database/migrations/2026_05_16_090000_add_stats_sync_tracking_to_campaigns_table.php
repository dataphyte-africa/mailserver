<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->timestamp('last_stats_sync_requested_at')->nullable()->after('sent_at');
            $table->timestamp('last_stats_sync_completed_at')->nullable()->after('last_stats_sync_requested_at');
            $table->string('last_stats_sync_status')->nullable()->after('last_stats_sync_completed_at');
            $table->unsignedInteger('last_stats_sync_total')->default(0)->after('last_stats_sync_status');
            $table->unsignedInteger('last_stats_sync_processed')->default(0)->after('last_stats_sync_total');
            $table->text('last_stats_sync_error')->nullable()->after('last_stats_sync_processed');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'last_stats_sync_requested_at',
                'last_stats_sync_completed_at',
                'last_stats_sync_status',
                'last_stats_sync_total',
                'last_stats_sync_processed',
                'last_stats_sync_error',
            ]);
        });
    }
};
