<?php

use Illuminate\Support\Facades\Route;
use Modules\IoT\Http\Controllers\IoTController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('iots', IoTController::class)->names('iot');
});
