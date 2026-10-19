<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('focal_point_applications', function (Blueprint $table): void {
            $table->string('tor_version')->nullable();
            $table->timestamp('tor_accepted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('focal_point_applications', function (Blueprint $table): void {
            $table->dropColumn(['tor_version', 'tor_accepted_at']);
        });
    }
};
