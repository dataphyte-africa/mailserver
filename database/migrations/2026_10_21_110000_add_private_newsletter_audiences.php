<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriber_groups', function (Blueprint $table): void {
            $table->string('audience_scope', 24)->default('product_public')->index();
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->string('audience_scope', 24)->default('product_public')->index();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', fn (Blueprint $table) => $table->dropColumn('audience_scope'));
        Schema::table('subscriber_groups', fn (Blueprint $table) => $table->dropColumn('audience_scope'));
    }
};
