<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statamic_group_scope_map', function (Blueprint $table) {
            $table->id();
            $table->string('group_handle')->unique();
            $table->string('scope_type');
            $table->foreignId('organisation_id')->nullable()->constrained('organisations')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->timestamps();

            $table->index('scope_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statamic_group_scope_map');
    }
};
