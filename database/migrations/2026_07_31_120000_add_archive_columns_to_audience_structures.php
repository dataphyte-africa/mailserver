<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriber_groups', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('description');
            $table->foreignId('archived_by')->nullable()->after('archived_at');
        });

        Schema::table('subscriber_sub_groups', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('description');
            $table->foreignId('archived_by')->nullable()->after('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriber_sub_groups', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'archived_by']);
        });

        Schema::table('subscriber_groups', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'archived_by']);
        });
    }
};
