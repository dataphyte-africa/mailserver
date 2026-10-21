<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_sends', function (Blueprint $table): void {
            $table->enum('status', [
                'queued', 'sent', 'delivered', 'opened', 'clicked',
                'bounced', 'failed', 'complained', 'skipped',
            ])->default('queued')->change();
        });
    }

    public function down(): void
    {
        DB::table('campaign_sends')->where('status', 'skipped')->update(['status' => 'failed']);
        Schema::table('campaign_sends', function (Blueprint $table): void {
            $table->enum('status', [
                'queued', 'sent', 'delivered', 'opened', 'clicked',
                'bounced', 'failed', 'complained',
            ])->default('queued')->change();
        });
    }
};
