<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('focal_point_application_drafts', function (Blueprint $table): void {
            $table->string('email_verification_code_hash', 64)->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_code_hash');
            $table->timestamp('email_verification_sent_at')->nullable()->after('email_verification_expires_at');
            $table->unsignedTinyInteger('email_verification_attempts')->default(0)->after('email_verification_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('focal_point_application_drafts', function (Blueprint $table): void {
            $table->dropColumn([
                'email_verification_code_hash',
                'email_verification_expires_at',
                'email_verification_sent_at',
                'email_verification_attempts',
            ]);
        });
    }
};
