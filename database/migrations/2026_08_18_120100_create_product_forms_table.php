<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_forms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('form_scope')->default('product');
            $table->string('product_selection_field')->nullable();
            $table->json('allowed_product_ids')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('mode');
            $table->string('template_family');
            $table->string('status')->default('published');
            $table->string('headline')->nullable();
            $table->text('description')->nullable();
            $table->string('success_message')->default('Submission received.');
            $table->json('field_definitions');
            $table->json('allowed_origins')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('requires_review')->default(true);
            $table->foreignId('audience_group_id')->nullable()->constrained('subscriber_groups')->nullOnDelete();
            $table->foreignId('audience_sub_group_id')->nullable()->constrained('subscriber_sub_groups')->nullOnDelete();
            $table->string('custom_extension_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_forms');
    }
};
