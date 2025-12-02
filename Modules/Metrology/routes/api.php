<?php

use Illuminate\Support\Facades\Route;
use Modules\Metrology\Http\Controllers\Api\V1\AccessLogApiController;
use Modules\Metrology\Http\Controllers\Api\V1\CalibrationApiController;
use Modules\Metrology\Http\Controllers\Api\V1\DashboardApiController;
use Modules\Metrology\Http\Controllers\Api\V1\InstrumentApiController;
use Modules\Metrology\Http\Controllers\Api\V1\InstrumentChecklistController;
use Modules\Metrology\Http\Controllers\MetrologyController;

//Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//    Route::apiResource('metrologies', MetrologyController::class)->names('metrology');
//});

Route::apiResource('metrologies', MetrologyController::class)->names('metrology');
Route::apiResource('instruments', InstrumentApiController::class);
Route::get('/instruments/{instrument}/checklists', [InstrumentChecklistController::class, 'index']);
Route::get('/checklists/{checklistTemplate}', [InstrumentChecklistController::class, 'show']);
Route::get('/dashboard/stats', [DashboardApiController::class, 'stats']);
Route::post('/access-logs', [AccessLogApiController::class, 'store']);
