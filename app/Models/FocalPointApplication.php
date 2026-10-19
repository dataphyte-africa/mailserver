<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FocalPointApplication extends Model
{
    protected $fillable = [
        'uuid', 'email', 'full_name', 'status', 'answers', 'tor_version', 'tor_html', 'tor_accepted_at',
        'cv_drive_id', 'cv_drive_url', 'cv_filename',
        'certificate_drive_id', 'certificate_drive_url', 'certificate_filename',
        'confirmation_email_status', 'confirmation_email_delivered_at',
        'confirmation_email_opened_at', 'confirmation_email_failed_at',
        'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'answers' => 'array',
        'reviewed_at' => 'datetime',
        'tor_accepted_at' => 'datetime',
        'confirmation_email_delivered_at' => 'datetime',
        'confirmation_email_opened_at' => 'datetime',
        'confirmation_email_failed_at' => 'datetime',
    ];
}
