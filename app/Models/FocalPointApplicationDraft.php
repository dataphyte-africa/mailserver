<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FocalPointApplicationDraft extends Model
{
    protected $fillable = [
        'uuid', 'email', 'answers', 'last_completed_step',
        'cv_drive_id', 'cv_drive_url', 'cv_filename',
        'certificate_drive_id', 'certificate_drive_url', 'certificate_filename',
        'resume_token_hash', 'resume_token_expires_at', 'email_verified_at', 'expires_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'resume_token_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
