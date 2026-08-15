<?php

$defaultOsunImportPath = base_path('docs/artifacts/osun-polling-unit-mapping.csv');

if (! is_file($defaultOsunImportPath)) {
    $fallbackOsunImportPath = '/Users/dataphytefoundation/Herd/mailserver/docs/artifacts/osun-polling-unit-mapping.csv';

    if (is_file($fallbackOsunImportPath)) {
        $defaultOsunImportPath = $fallbackOsunImportPath;
    }
}

return [

    'evidence_disk' => env('OBSERVER_VIOLATIONS_EVIDENCE_DISK', 'local'),

    'evidence_max_file_size_kb' => (int) env('OBSERVER_VIOLATIONS_EVIDENCE_MAX_FILE_SIZE_KB', 20480),

    'evidence_allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'video/mp4',
        'video/quicktime',
        'audio/mpeg',
        'audio/mp4',
        'audio/wav',
        'audio/webm',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ],

    'osun_polling_units_import_path' => env(
        'OBSERVER_VIOLATIONS_OSUN_POLLING_UNITS_IMPORT_PATH',
        $defaultOsunImportPath
    ),

    'violation_category' => 'voter intimidation or harassment',

];
