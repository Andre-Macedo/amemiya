<?php

use Illuminate\Support\Facades\Route;
use Modules\Metrology\Http\Controllers\CalibrationPdfController;
use Modules\Metrology\Http\Controllers\MetrologyController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('metrologies', MetrologyController::class)->names('metrology');
});
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/calibrations/{calibration}/certificate', [CalibrationPdfController::class, 'download'])
        ->name('calibration.certificate.download');
});
