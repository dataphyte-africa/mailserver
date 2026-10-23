<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('focal_point_applications')->select('email')->groupBy('email')->havingRaw('count(*) > 1')->exists()) {
            throw new RuntimeException('Resolve duplicate focal point application email addresses before running this migration.');
        }

        Schema::table('focal_point_applications', function (Blueprint $table): void {
            $table->unique('email');
        });

        Schema::table('focal_point_application_drafts', function (Blueprint $table): void {
            foreach (['cv', 'certificate'] as $kind) {
                $table->string("{$kind}_upload_path")->nullable()->after("{$kind}_filename");
                $table->string("{$kind}_upload_filename")->nullable()->after("{$kind}_upload_path");
                $table->string("{$kind}_upload_status")->default('ready')->after("{$kind}_upload_filename");
                $table->string("{$kind}_upload_error")->nullable()->after("{$kind}_upload_status");
                $table->timestamp("{$kind}_upload_queued_at")->nullable()->after("{$kind}_upload_error");
                $table->timestamp("{$kind}_uploaded_at")->nullable()->after("{$kind}_upload_queued_at");
            }
        });
    }

    public function down(): void
    {
        Schema::table('focal_point_application_drafts', function (Blueprint $table): void {
            foreach (['cv', 'certificate'] as $kind) {
                $table->dropColumn([
                    "{$kind}_upload_path",
                    "{$kind}_upload_filename",
                    "{$kind}_upload_status",
                    "{$kind}_upload_error",
                    "{$kind}_upload_queued_at",
                    "{$kind}_uploaded_at",
                ]);
            }
        });

        Schema::table('focal_point_applications', function (Blueprint $table): void {
            $table->dropUnique(['email']);
        });
    }
};
