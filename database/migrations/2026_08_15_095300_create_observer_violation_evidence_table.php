<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observer_violation_evidence', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('observer_violation_report_id')
                ->constrained('observer_violation_reports')
                ->cascadeOnDelete();
            $table->string('evidence_kind')->default('file');
            $table->string('storage_disk')->nullable();
            $table->text('object_path')->nullable();
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('upload_status')->default('pending');
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observer_violation_evidence');
    }
};
