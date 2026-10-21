<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriber_sub_group', function (Blueprint $table): void {
            $table->string('private_source', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriber_sub_group', fn (Blueprint $table) => $table->dropColumn('private_source'));
    }
};
