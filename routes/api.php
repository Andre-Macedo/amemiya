<?php

use Illuminate\Support\Facades\Route;
use Modules\System\Http\Controllers\Api\V1\AuthApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Grupo V1 - Apenas rotas centrais de autenticação aqui.
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login'])->name('api.v1.login');
    Route::post('/login/2fa', [AuthApiController::class, 'verifyTwoFactor'])->name('api.v1.login.2fa');
    Route::post('/impersonate', [AuthApiController::class, 'impersonate'])->name('api.v1.impersonate');
});
