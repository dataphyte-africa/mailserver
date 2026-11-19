<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tor_flows', function (Blueprint $table): void {
            $table->json('email_copy')->nullable()->after('internal_recipients');
        });
    }

    public function down(): void
    {
        Schema::table('tor_flows', function (Blueprint $table): void {
            $table->dropColumn('email_copy');
        });
    }
};
