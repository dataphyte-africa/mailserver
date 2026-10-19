<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisations', function (Blueprint $table): void {
            $table->text('postal_address')->nullable();
            $table->string('website_url')->nullable();
            $table->json('social_links')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('brand_primary_color', 7)->nullable();
            $table->string('brand_secondary_color', 7)->nullable();
            $table->text('unsubscribe_message')->nullable();
        });

        Schema::table('subscriber_groups', function (Blueprint $table): void {
            $table->boolean('is_public_preference')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('subscriber_groups', fn (Blueprint $table) => $table->dropColumn('is_public_preference'));
        Schema::table('organisations', fn (Blueprint $table) => $table->dropColumn([
            'postal_address', 'website_url', 'social_links', 'logo_path', 'banner_path',
            'brand_primary_color', 'brand_secondary_color', 'unsubscribe_message',
        ]));
    }
};
