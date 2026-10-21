<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('focal_point_application_drafts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('email')->index();
            $table->json('answers')->nullable();
            $table->unsignedTinyInteger('last_completed_step')->default(0);
            $table->string('cv_drive_id')->nullable();
            $table->string('cv_drive_url')->nullable();
            $table->string('cv_filename')->nullable();
            $table->string('certificate_drive_id')->nullable();
            $table->string('certificate_drive_url')->nullable();
            $table->string('certificate_filename')->nullable();
            $table->string('resume_token_hash', 64)->nullable();
            $table->timestamp('resume_token_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('focal_point_application_drafts');
    }
};
