<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriber_groups', function (Blueprint $table) {
            $table->foreignId('organisation_id')
                ->nullable()
                ->constrained('organisations')
                ->nullOnDelete();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('organisation_id')
                ->nullable()
                ->constrained('organisations')
                ->nullOnDelete();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->foreignId('organisation_id')
                ->nullable()
                ->constrained('organisations')
                ->nullOnDelete();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('organisation_id');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('organisation_id');
        });

        Schema::table('subscriber_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('organisation_id');
        });
    }
};
