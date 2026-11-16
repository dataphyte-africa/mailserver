<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tor_flows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content_html');
            $table->json('internal_recipients')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tor_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tor_flow_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('expected_name')->nullable();
            $table->string('token', 96)->unique();
            $table->string('status')->default('pending')->index();
            $table->string('elastic_email_transaction_id')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signature_method')->nullable();
            $table->longText('signature_data')->nullable();
            $table->string('verification_code', 64)->nullable()->unique();
            $table->string('document_hash', 64)->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('revoked_reason')->nullable();
            $table->timestamps();
            $table->unique(['tor_flow_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tor_invitations');
        Schema::dropIfExists('tor_flows');
    }
};
