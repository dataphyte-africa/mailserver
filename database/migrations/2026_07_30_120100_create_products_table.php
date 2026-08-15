<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('product_type');
            $table->string('public_domain')->nullable();
            $table->string('mail_from_domain')->nullable();
            $table->string('forms_domain')->nullable();
            $table->string('domain_status')->default('unconfigured');
            $table->timestamp('domain_verified_at')->nullable();
            $table->boolean('domain_is_primary')->default(false);
            $table->string('primary_collection_handle')->nullable();
            $table->json('default_sender_profile')->nullable();
            $table->string('default_template_family')->nullable();
            $table->boolean('fallback_to_platform_domain')->default(true);
            $table->timestamps();

            $table->index(['organisation_id', 'status']);
            $table->index('product_type');
            $table->index('primary_collection_handle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
