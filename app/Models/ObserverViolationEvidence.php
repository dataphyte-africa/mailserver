<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObserverViolationEvidence extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'observer_violation_report_id',
        'evidence_kind',
        'storage_disk',
        'object_path',
        'original_filename',
        'mime_type',
        'file_size',
        'checksum',
        'upload_status',
        'failure_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ObserverViolationReport::class, 'observer_violation_report_id');
    }
}
