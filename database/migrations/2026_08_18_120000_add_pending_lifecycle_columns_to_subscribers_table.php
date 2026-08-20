<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->unsignedTinyInteger('pending_confirmation_resend_count')
                ->default(0)
                ->after('confirmed_at');
            $table->timestamp('pending_confirmation_last_resent_at')
                ->nullable()
                ->after('pending_confirmation_resend_count');
            $table->timestamp('pending_confirmation_expires_at')
                ->nullable()
                ->after('pending_confirmation_last_resent_at');
            $table->string('pending_lifecycle_state')
                ->nullable()
                ->after('pending_confirmation_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn([
                'pending_confirmation_resend_count',
                'pending_confirmation_last_resent_at',
                'pending_confirmation_expires_at',
                'pending_lifecycle_state',
            ]);
        });
    }
};
