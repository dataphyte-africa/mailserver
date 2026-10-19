<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->string('code', 3)->primary();
            $table->string('name');
        });

        // Statamic ships the ISO 3166-1 catalogue; persist it so forms query one shared source.
        $countries = require base_path('vendor/statamic/cms/lang/en/dictionary-countries.php');
        foreach (array_chunk($countries['names'], 100, true) as $chunk) {
            DB::table('countries')->insert(collect($chunk)->map(
                fn (string $name, string $code): array => ['code' => $code, 'name' => $name]
            )->values()->all());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
