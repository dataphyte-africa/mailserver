<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('focal_point_applications')
            ->where('confirmation_email_status', 'confirmed')
            ->update(['confirmation_email_status' => 'opened']);

        if (Schema::hasColumn('focal_point_applications', 'status')) {
            Schema::table('focal_point_applications', function (Blueprint $table): void {
                $table->dropIndex(['status']);
                $table->dropColumn(['status', 'reviewed_at', 'reviewed_by']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('focal_point_applications', 'status')) {
            Schema::table('focal_point_applications', function (Blueprint $table): void {
                $table->string('status')->default('pending_review')->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->string('reviewed_by')->nullable();
            });
        }

        DB::table('focal_point_applications')
            ->where('confirmation_email_status', 'opened')
            ->update(['confirmation_email_status' => 'confirmed']);
    }
};
