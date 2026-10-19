<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FocalPointApplication extends Model
{
    protected $fillable = [
        'uuid', 'email', 'full_name', 'status', 'answers', 'tor_version', 'tor_html', 'tor_accepted_at',
        'cv_drive_id', 'cv_drive_url', 'cv_filename',
        'certificate_drive_id', 'certificate_drive_url', 'certificate_filename',
        'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'answers' => 'array',
        'reviewed_at' => 'datetime',
        'tor_accepted_at' => 'datetime',
    ];
}
