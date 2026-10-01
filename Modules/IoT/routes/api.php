<?php

use App\Http\Middleware\InitializeTenancyByHeader;
use Illuminate\Support\Facades\Route;
use Modules\IoT\Http\Controllers\Api\V1\IoTDeviceLogApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTGatewayApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTHistoryApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTNodeApiController;

Route::middleware([
    'auth:sanctum',
    InitializeTenancyByHeader::class,
])->prefix('v1')->group(function () {
    Route::get('iot-history', [IoTHistoryApiController::class, 'index']);
    Route::apiResource('iot-gateways', IoTGatewayApiController::class);
    Route::apiResource('iot-nodes', IoTNodeApiController::class);
    Route::apiResource('iot-logs', IoTDeviceLogApiController::class)->only(['index', 'show']);
});
