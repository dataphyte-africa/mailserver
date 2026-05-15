<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriber_groups', function (Blueprint $table) {
            $table->string('collection_handle')->nullable()->after('slug');
            $table->index('collection_handle');
        });

        $knownGroups = collect(config('newsletter.collections', []))
            ->mapWithKeys(fn (array $config, string $handle) => [
                $config['group_slug'] ?? null => $handle,
            ])
            ->filter();

        foreach ($knownGroups as $slug => $handle) {
            DB::table('subscriber_groups')
                ->where('slug', $slug)
                ->update(['collection_handle' => $handle]);
        }
    }

    public function down(): void
    {
        Schema::table('subscriber_groups', function (Blueprint $table) {
            $table->dropIndex(['collection_handle']);
            $table->dropColumn('collection_handle');
        });
    }
};
