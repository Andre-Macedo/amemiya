<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Agrupamos todas as rotas da V1 sob um prefixo 'v1'
Route::prefix('v1')->group(function () {

    // --- Rotas Públicas da V1 ---
    Route::post('/login', [AuthApiController::class, 'login'])->name('api.v1.login');

    // --- Rotas Protegidas da V1 ---
    Route::middleware('auth:sanctum')->group(function () {

        // Rotas de Autenticação/Usuário
        Route::get('/user', fn(Request $request) => $request->user())->name('api.v1.user');
        Route::post('/logout', [AuthApiController::class, 'logout'])->name('api.v1.logout');

    });
});
