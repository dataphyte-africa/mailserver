<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_subscriber_opt_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at');
            $table->unique(['organisation_id', 'subscriber_id'], 'organisation_subscriber_opt_out_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_subscriber_opt_outs');
    }
};
