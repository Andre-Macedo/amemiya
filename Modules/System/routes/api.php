<?php

use App\Http\Middleware\InitializeTenancyByHeader;
use App\Http\Middleware\VerifySubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Http\Controllers\Api\V1\AuthApiController;
use Modules\System\Http\Controllers\Api\V1\AuditLogApiController;
use Modules\System\Http\Controllers\Api\V1\BillingApiController;
use Modules\System\Http\Controllers\Api\V1\LabIdentityApiController;
use Modules\System\Http\Controllers\Api\V1\NotificationApiController;
use Modules\System\Http\Controllers\Api\V1\ProfileApiController;
use Modules\System\Http\Controllers\Api\V1\SettingsApiController;
use Modules\System\Http\Controllers\Api\V1\StationApiController;
use Modules\System\Http\Controllers\Api\V1\MachineApiController;
use Modules\System\Http\Controllers\Api\V1\SupplierApiController;
use Modules\System\Http\Controllers\Api\V1\TicketApiController;
use Modules\System\Http\Controllers\Api\V1\UserApiController;
use Modules\System\Http\Controllers\Api\V1\UserCompetenceApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/*
|--------------------------------------------------------------------------
| API Routes - Módulo System
|--------------------------------------------------------------------------
*/

// Middleware de Tenancy deve vir ANTES do Sanctum para que o banco do tenant seja selecionado
Route::middleware([
    InitializeTenancyByHeader::class,
    'throttle:api',
])->group(function () {

    Route::middleware(['auth:sanctum', VerifySubscription::class])->group(function () {

        Route::prefix('system')->group(function () {
            // Perfil base (Movido do api.php raiz para cá)
            Route::get('/user', fn (Request $request) => $request->user());
            Route::post('/logout', [AuthApiController::class, 'logout']);

            // Auth & Profile
            Route::get('profile', [ProfileApiController::class, 'show']);
            Route::put('profile', [ProfileApiController::class, 'update']);
            Route::post('profile/accept-legal', [ProfileApiController::class, 'acceptLegal']);

            // Support Tickets
            Route::apiResource('tickets', TicketApiController::class);
            Route::post('tickets/{ticket}/messages', [TicketApiController::class, 'addMessage']);

            // Billing
            Route::get('billing', [BillingApiController::class, 'index']);

            // Stations
            Route::apiResource('stations', StationApiController::class);

            // Machines
            Route::get('machines', [MachineApiController::class, 'index']);

            // Suppliers
            Route::apiResource('suppliers', SupplierApiController::class);

            // Users & Roles
            Route::apiResource('users', UserApiController::class);
            Route::get('roles', [UserApiController::class, 'getRoles']);

            // Competences
            Route::get('users/{user}/competences', [UserCompetenceApiController::class, 'index']);
            Route::post('users/{user}/competences', [UserCompetenceApiController::class, 'sync']);

            // Lab Identity
            Route::get('lab-identity', [LabIdentityApiController::class, 'show']);
            Route::post('lab-identity', [LabIdentityApiController::class, 'update']);

            // Settings
            Route::get('settings', [SettingsApiController::class, 'index']);
            Route::put('settings', [SettingsApiController::class, 'update']);

            // Notifications
            Route::get('notifications', [NotificationApiController::class, 'index']);
            Route::get('notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
            Route::post('notifications/{notification}/read', [NotificationApiController::class, 'markAsRead']);
            Route::post('notifications/read-all', [NotificationApiController::class, 'markAllAsRead']);

            // Logs
            Route::get('audit-logs', [AuditLogApiController::class, 'index']);
        });
    });
});
