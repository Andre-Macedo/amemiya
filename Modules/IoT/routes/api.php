<?php

use App\Http\Middleware\InitializeTenancyByHeader;
use Illuminate\Support\Facades\Route;
use Modules\IoT\Http\Controllers\Api\V1\IoTDeviceLogApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTGatewayApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTHistoryApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTMLDatasetApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTMLModelApiController;
use Modules\IoT\Http\Controllers\Api\V1\IoTNodeApiController;

Route::middleware([
    'auth:sanctum',
    InitializeTenancyByHeader::class,
])->prefix('v1')->group(function () {
    Route::get('iot-history', [IoTHistoryApiController::class, 'index']);
    Route::apiResource('iot-gateways', IoTGatewayApiController::class);
    Route::apiResource('iot-nodes', IoTNodeApiController::class);
    Route::apiResource('iot-logs', IoTDeviceLogApiController::class)->only(['index', 'show']);

    // MLOps: Datasets, Rotulação A Posteriori & Model Registry
    Route::apiResource('iot-datasets', IoTMLDatasetApiController::class)->only(['index', 'store', 'show']);
    Route::post('iot-logs/{id}/label', [IoTMLDatasetApiController::class, 'labelLog']);
    Route::get('iot-datasets/{id}/export', [IoTMLDatasetApiController::class, 'export']);
    Route::apiResource('iot-models', IoTMLModelApiController::class)->only(['index', 'store']);
    Route::post('iot-models/{id}/deploy', [IoTMLModelApiController::class, 'deploy']);
});
