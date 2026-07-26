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
        'birth_certificate' => 'Birth Certificate (required under 18)',
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

    /*
    | Admission form validation aligned to common Kenyan higher-education practice
    | (CUE degree floor C+, typical Form 4 exit ages, KNEC index format, NRB/Maisha IDs).
    */
    'admission' => [
        // Diploma / certificate applicants (private / TVET pathways)
        'min_age_years' => 16,
        // Undergraduate degree applicants (typically 17–18 at intake after KCSE)
        'min_age_degree_years' => 17,
        'max_age_years' => 70,
        'guardian_required_under_years' => 18,
        'kcse_first_year' => 1989,
        // KNEC index numbers are normally 11 digits; allow nearby legacy lengths
        'kcse_index_min_digits' => 10,
        'kcse_index_max_digits' => 12,
        'min_age_at_kcse' => 14,
        'max_age_at_kcse' => 30,
    ],
];
