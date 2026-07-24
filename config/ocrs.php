<?php

return [
    'institution_name' => env('OCRS_INSTITUTION_NAME', 'Private University College'),
    'institution_code' => env('OCRS_INSTITUTION_CODE', 'PUC'),
    'support_email' => env('OCRS_SUPPORT_EMAIL', 'registrar@institution.ac.ke'),
    'support_phone' => env('OCRS_SUPPORT_PHONE', '+254700000000'),
    'data_retention_years' => env('OCRS_DATA_RETENTION_YEARS', 7),
    'document_types' => [
        'kcse_certificate' => 'KCSE Certificate / Result Slip',
        'national_id' => 'National ID / Passport',
        'birth_certificate' => 'Birth Certificate (if no National ID)',
        'passport_photo' => 'Passport-size Photo',
        'degree_certificate' => 'Degree / Diploma Certificate (postgraduate / credit transfer)',
    ],
    // Typical private institution clearance: allow unit registration after partial tuition
    'default_min_tuition_percentage' => 50,
    'kcse_grade_map' => [
        'A' => 12, 'A-' => 11, 'B+' => 10, 'B' => 9, 'B-' => 8,
        'C+' => 7, 'C' => 6, 'C-' => 5, 'D+' => 4, 'D' => 3,
        'D-' => 2, 'E' => 1,
    ],
];
