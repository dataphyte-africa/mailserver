<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisations', function (Blueprint $table): void {
            if (! Schema::hasColumn('organisations', 'primary_collection_handle')) {
                $table->string('primary_collection_handle')->nullable()->after('status')->index();
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'blueprint_handle')) {
                $table->string('blueprint_handle')->nullable()->after('primary_collection_handle')->index();
            }
        });

        Schema::table('product_forms', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_forms', 'form_scope')) {
                $table->string('form_scope')->default('product')->after('product_id');
            }

            if (! Schema::hasColumn('product_forms', 'product_selection_field')) {
                $table->string('product_selection_field')->nullable()->after('form_scope');
            }

            if (! Schema::hasColumn('product_forms', 'allowed_product_ids')) {
                $table->json('allowed_product_ids')->nullable()->after('product_selection_field');
            }
        });

        if (Schema::hasColumn('product_forms', 'product_id')) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE product_forms MODIFY product_id BIGINT UNSIGNED NULL');
            }
        }
    }

    public function down(): void
    {
        Schema::table('product_forms', function (Blueprint $table): void {
            if (Schema::hasColumn('product_forms', 'allowed_product_ids')) {
                $table->dropColumn('allowed_product_ids');
            }

            if (Schema::hasColumn('product_forms', 'product_selection_field')) {
                $table->dropColumn('product_selection_field');
            }

            if (Schema::hasColumn('product_forms', 'form_scope')) {
                $table->dropColumn('form_scope');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'blueprint_handle')) {
                $table->dropColumn('blueprint_handle');
            }
        });

        Schema::table('organisations', function (Blueprint $table): void {
            if (Schema::hasColumn('organisations', 'primary_collection_handle')) {
                $table->dropColumn('primary_collection_handle');
            }
        });
    }
};
