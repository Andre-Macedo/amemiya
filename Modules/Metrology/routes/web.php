<?php

use Illuminate\Support\Facades\Route;
use Modules\Metrology\Http\Controllers\MetrologyController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('metrologies', MetrologyController::class)->names('metrology');
});
