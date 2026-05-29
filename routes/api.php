<?php

use App\Http\Middleware\InitializeTenancyByHeader;
use App\Http\Middleware\VerifySubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Http\Controllers\Api\V1\AuthApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Grupo V1 - Apenas rotas centrais de autenticação aqui.
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login'])->name('api.v1.login');
    Route::post('/impersonate', [AuthApiController::class, 'impersonate'])->name('api.v1.impersonate');
});
