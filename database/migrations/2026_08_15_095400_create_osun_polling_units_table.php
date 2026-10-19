<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osun_polling_units', function (Blueprint $table) {
            $table->id();
            $table->string('state')->default('Osun');
            $table->string('lga');
            $table->string('ward');
            $table->string('polling_unit_code', 50)->unique();
            $table->string('polling_unit_name');
            $table->text('source_url')->nullable();
            $table->timestamps();

            $table->index(['lga', 'ward']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osun_polling_units');
    }
};
