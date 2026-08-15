<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_user_scope', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->string('scope_role');
            $table->string('status')->default('active');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'organisation_id']);
            $table->index(['organisation_id', 'status']);
            $table->index('scope_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_user_scope');
    }
};
