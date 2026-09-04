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
];
