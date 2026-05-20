<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscribers')
            ->where('engagement_rating', 'engaged')
            ->update(['engagement_rating' => 'high']);

        DB::table('subscribers')
            ->where('engagement_rating', 'warm')
            ->update(['engagement_rating' => 'moderate']);

        DB::table('subscribers')
            ->where('engagement_rating', 'cold')
            ->update(['engagement_rating' => 'low']);

        DB::table('subscribers')
            ->where('engagement_rating', 'at_risk')
            ->update(['engagement_rating' => 'inactive']);
    }

    public function down(): void
    {
        DB::table('subscribers')
            ->where('engagement_rating', 'high')
            ->update(['engagement_rating' => 'engaged']);

        DB::table('subscribers')
            ->where('engagement_rating', 'moderate')
            ->update(['engagement_rating' => 'warm']);

        DB::table('subscribers')
            ->where('engagement_rating', 'low')
            ->update(['engagement_rating' => 'cold']);

        DB::table('subscribers')
            ->where('engagement_rating', 'inactive')
            ->update(['engagement_rating' => 'at_risk']);
    }
};
