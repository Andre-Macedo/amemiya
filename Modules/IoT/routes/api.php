<?php

use Illuminate\Support\Facades\Route;
use Modules\IoT\Http\Controllers\Api\V1\IoTGatewayApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTNodeApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTHistoryApiController;

Route::middleware([
    'auth:sanctum',
    \App\Http\Middleware\InitializeTenancyByHeader::class,
])->prefix('v1')->group(function () {
    Route::get('iot-history', [IoTHistoryApiController::class, 'index']);
    Route::apiResource('iot-gateways', IoTGatewayApiController::class);
    Route::apiResource('iot-nodes', IoTNodeApiController::class);
});
