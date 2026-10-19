<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_form_submissions', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('product_form_submissions')->whereNull('product_id')->exists()) {
            throw new RuntimeException('Assign or remove organisation-wide form submissions before rolling back this migration.');
        }

        Schema::table('product_form_submissions', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
