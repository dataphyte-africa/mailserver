<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observer_violation_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status')->default('submitted');
            $table->string('observer_full_name');
            $table->string('observer_phone_number', 30);
            $table->string('observer_email');
            $table->string('observer_organisation');
            $table->string('observer_id_or_deployment_code');
            $table->string('observer_assigned_state');
            $table->string('observer_assigned_lga');
            $table->string('observer_assigned_ward');
            $table->string('observer_assigned_polling_unit_code', 50);
            $table->string('observer_assigned_polling_unit_name');
            $table->string('observer_role');
            $table->string('observer_verification_status', 100);
            $table->string('incident_state');
            $table->string('incident_lga');
            $table->string('incident_ward');
            $table->string('incident_polling_unit_code', 50);
            $table->string('incident_polling_unit_name');
            $table->string('incident_address_or_landmark', 500);
            $table->decimal('incident_gps_latitude', 10, 7)->nullable();
            $table->decimal('incident_gps_longitude', 10, 7)->nullable();
            $table->date('incident_date');
            $table->time('incident_time_observed');
            $table->boolean('incident_is_ongoing');
            $table->string('violation_category');
            $table->longText('incident_description');
            $table->longText('evidence_description')->nullable();
            $table->longText('witness_statement')->nullable();
            $table->json('external_references')->nullable();
            $table->boolean('evidence_consent_confirmed');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['incident_lga', 'incident_ward']);
            $table->index('incident_polling_unit_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observer_violation_reports');
    }
};
