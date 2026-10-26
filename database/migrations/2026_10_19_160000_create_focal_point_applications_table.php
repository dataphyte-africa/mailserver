<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('focal_point_applications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('email')->index();
            $table->string('full_name');
            $table->json('answers');
            $table->string('cv_drive_id');
            $table->string('cv_drive_url');
            $table->string('cv_filename');
            $table->string('certificate_drive_id');
            $table->string('certificate_drive_url');
            $table->string('certificate_filename');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('focal_point_applications');
    }
};
