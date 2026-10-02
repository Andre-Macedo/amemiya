<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Rubric / Signature Position
    |--------------------------------------------------------------------------
    |
    | Default coordinates and dimensions for stamping the technician / approver
    | rubric image onto calibration certificates.
    |
    */
    'pdf_rubric_position' => [
        'x' => (float) env('PDF_RUBRIC_X', 140),
        'y' => (float) env('PDF_RUBRIC_Y', 250),
        'w' => (float) env('PDF_RUBRIC_W', 40),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laboratorial Digital Certificate (X.509 / PKCS#12 / PEM)
    |--------------------------------------------------------------------------
    |
    | Path and password for the digital certificate (.pfx/.p12/.pem) used to
    | cryptographically sign calibration certificates per FDA 21 CFR Part 11
    | and ISO/IEC 17025.
    |
    */
    'certificate_path' => env('METROLOGY_CERTIFICATE_PATH'),
    'certificate_password' => env('METROLOGY_CERTIFICATE_PASSWORD'),
];
