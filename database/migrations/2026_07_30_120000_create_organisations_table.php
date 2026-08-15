<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('default_domain')->nullable();
            $table->string('default_mail_domain')->nullable();
            $table->string('default_from_name')->nullable();
            $table->string('default_reply_to')->nullable();
            $table->json('compliance_profile')->nullable();
            $table->string('support_contact')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisations');
    }
};
