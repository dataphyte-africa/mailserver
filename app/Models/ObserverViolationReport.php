<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObserverViolationReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'status',
        'observer_full_name',
        'observer_phone_number',
        'observer_email',
        'observer_organisation',
        'observer_id_or_deployment_code',
        'observer_assigned_state',
        'observer_assigned_lga',
        'observer_assigned_ward',
        'observer_assigned_polling_unit_code',
        'observer_assigned_polling_unit_name',
        'observer_role',
        'observer_verification_status',
        'incident_state',
        'incident_lga',
        'incident_ward',
        'incident_polling_unit_code',
        'incident_polling_unit_name',
        'incident_address_or_landmark',
        'incident_gps_latitude',
        'incident_gps_longitude',
        'incident_date',
        'incident_time_observed',
        'incident_is_ongoing',
        'violation_category',
        'incident_description',
        'evidence_description',
        'witness_statement',
        'external_references',
        'evidence_consent_confirmed',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'incident_is_ongoing' => 'boolean',
            'evidence_consent_confirmed' => 'boolean',
            'external_references' => 'array',
            'submitted_at' => 'datetime',
            'incident_gps_latitude' => 'decimal:7',
            'incident_gps_longitude' => 'decimal:7',
        ];
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ObserverViolationEvidence::class);
    }
}
