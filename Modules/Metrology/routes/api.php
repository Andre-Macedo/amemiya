<?php

use Illuminate\Support\Facades\Route;
use Modules\Metrology\Http\Controllers\Api\V1\AccessLogApiController;
use Modules\Metrology\Http\Controllers\Api\V1\InstrumentApiController;
use Modules\Metrology\Http\Controllers\MetrologyController;

//Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//    Route::apiResource('metrologies', MetrologyController::class)->names('metrology');
//});

Route::apiResource('metrologies', MetrologyController::class)->names('metrology');
Route::apiResource('instruments', InstrumentApiController::class);
Route::post('/access-logs', [AccessLogApiController::class, 'store']);
