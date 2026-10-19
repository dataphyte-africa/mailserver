<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('focal_point_applications', function (Blueprint $table): void {
            $table->string('confirmation_email_status')->default('pending')->index();
            $table->timestamp('confirmation_email_delivered_at')->nullable();
            $table->timestamp('confirmation_email_opened_at')->nullable();
            $table->timestamp('confirmation_email_failed_at')->nullable();
        });

        DB::table('focal_point_applications')->update(['confirmation_email_status' => 'not_sent']);
    }

    public function down(): void
    {
        Schema::table('focal_point_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'confirmation_email_status',
                'confirmation_email_delivered_at',
                'confirmation_email_opened_at',
                'confirmation_email_failed_at',
            ]);
        });
    }
};
