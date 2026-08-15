<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OsunPollingUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'state',
        'lga',
        'ward',
        'polling_unit_code',
        'polling_unit_name',
        'source_url',
    ];
}
