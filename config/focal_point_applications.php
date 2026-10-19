<?php

return [
    'closes_at' => env('FOCAL_POINT_APPLICATIONS_CLOSES_AT', '2026-10-29 23:59:00'),
    'timezone' => 'Africa/Lagos',
    'max_upload_kb' => (int) env('FOCAL_POINT_APPLICATION_MAX_UPLOAD_KB', 10240),
    'google_drive' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'cv_folder_id' => env('GOOGLE_DRIVE_CV_FOLDER_ID', '13syUofa223-tCfO_gCWP4gcCe8XZVaf6'),
        'certificate_folder_id' => env('GOOGLE_DRIVE_CERTIFICATE_FOLDER_ID', '1ti9A02QxtSBFzpregUK2uaXNa9Iw0F9k'),
    ],
];
