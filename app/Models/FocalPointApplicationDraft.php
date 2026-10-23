<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FocalPointApplicationDraft extends Model
{
    protected $fillable = [
        'uuid', 'email', 'answers', 'last_completed_step',
        'cv_drive_id', 'cv_drive_url', 'cv_filename',
        'certificate_drive_id', 'certificate_drive_url', 'certificate_filename',
        'cv_upload_path', 'cv_upload_filename', 'cv_upload_status', 'cv_upload_error',
        'certificate_upload_path', 'certificate_upload_filename', 'certificate_upload_status', 'certificate_upload_error',
        'cv_upload_queued_at', 'cv_uploaded_at', 'certificate_upload_queued_at', 'certificate_uploaded_at',
        'resume_token_hash', 'resume_token_expires_at', 'email_verified_at', 'expires_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'resume_token_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'cv_upload_queued_at' => 'datetime',
        'cv_uploaded_at' => 'datetime',
        'certificate_upload_queued_at' => 'datetime',
        'certificate_uploaded_at' => 'datetime',
    ];
}
