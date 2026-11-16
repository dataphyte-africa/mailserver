<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tor_invitations', function (Blueprint $table): void {
            $table->string('state')->nullable()->after('expected_name');
            $table->string('position')->nullable()->after('state');
            $table->string('phone_number')->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('tor_invitations', function (Blueprint $table): void {
            $table->dropColumns(['state', 'position', 'phone_number']);
        });
    }
};
