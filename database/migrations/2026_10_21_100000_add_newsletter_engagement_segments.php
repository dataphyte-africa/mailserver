<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriber_sub_groups', function (Blueprint $table) {
            $table->string('purpose', 24)->default('preference')->index();
        });

        Schema::create('subscriber_group_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_group_id')->constrained()->cascadeOnDelete();
            $table->string('rating', 24)->default('new');
            $table->boolean('is_member')->default(true);
            $table->integer('score')->default(0);
            $table->timestamp('first_joined_at');
            $table->timestamp('last_engaged_at')->nullable();
            $table->timestamp('last_recalculated_at')->nullable();
            $table->timestamps();
            $table->unique(['subscriber_id', 'subscriber_group_id'], 'subscriber_group_engagement_unique');
            $table->index(['subscriber_group_id', 'rating']);
        });

        Schema::create('organisation_engagement_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('interval_days')->default(30);
            $table->timestamp('last_recalculated_at')->nullable();
            $table->timestamp('last_requested_at')->nullable();
            $table->string('last_status', 24)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_engagement_settings');
        Schema::dropIfExists('subscriber_group_engagements');
        Schema::table('subscriber_sub_groups', fn (Blueprint $table) => $table->dropColumn('purpose'));
    }
};
