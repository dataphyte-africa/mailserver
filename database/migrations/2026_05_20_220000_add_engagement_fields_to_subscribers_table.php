<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->integer('engagement_score')->nullable()->after('metadata');
            $table->string('engagement_rating', 32)->nullable()->after('engagement_score');
            $table->timestamp('last_engaged_at')->nullable()->after('engagement_rating');

            $table->index('engagement_rating');
            $table->index('last_engaged_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropIndex(['engagement_rating']);
            $table->dropIndex(['last_engaged_at']);
            $table->dropColumn(['engagement_score', 'engagement_rating', 'last_engaged_at']);
        });
    }
};
