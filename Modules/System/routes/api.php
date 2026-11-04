<?php

use App\Http\Middleware\VerifySubscription;
use Illuminate\Support\Facades\Route;
use Modules\System\Http\Controllers\Api\V1\AccessLogApiController;
use Modules\System\Http\Controllers\Api\V1\AuditLogApiController;
use Modules\System\Http\Controllers\Api\V1\AuthApiController;
use Modules\System\Http\Controllers\Api\V1\BillingApiController;
use Modules\System\Http\Controllers\Api\V1\CertificateApiController;
use Modules\System\Http\Controllers\Api\V1\LabIdentityApiController;
use Modules\System\Http\Controllers\Api\V1\MachineApiController;
use Modules\System\Http\Controllers\Api\V1\NotificationApiController;
use Modules\System\Http\Controllers\Api\V1\ProfileApiController;
use Modules\System\Http\Controllers\Api\V1\SettingsApiController;
use Modules\System\Http\Controllers\Api\V1\StationApiController;
use Modules\System\Http\Controllers\Api\V1\SupplierApiController;
use Modules\System\Http\Controllers\Api\V1\TicketApiController;
use Modules\System\Http\Controllers\Api\V1\UserApiController;
use Modules\System\Http\Controllers\Api\V1\UserCompetenceApiController;

/*
|--------------------------------------------------------------------------
| API Routes - Módulo System
|--------------------------------------------------------------------------
*/

// Middleware de Tenancy estrito deve vir ANTES do Sanctum para que o tenant seja identificado e validado
Route::middleware([
    'tenant.enforce',
    'throttle:api',
])->group(function () {

    Route::middleware(['auth:sanctum', VerifySubscription::class])->group(function () {

        // Rotas raiz para compatibilidade com clientes mobile e web
        Route::apiResource('stations', StationApiController::class);
        Route::post('machines/{machine}/command', [MachineApiController::class, 'sendCommand']);
        Route::apiResource('machines', MachineApiController::class);
        Route::apiResource('suppliers', SupplierApiController::class);

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
            Route::apiResource('stations', StationApiController::class)->names('system.stations');

            // Machines
            Route::apiResource('machines', MachineApiController::class)->names('system.machines');

            // Suppliers
            Route::apiResource('suppliers', SupplierApiController::class)->names('system.suppliers');

            // Users & Roles
            Route::apiResource('users', UserApiController::class);
            Route::get('roles', [UserApiController::class, 'getRoles']);

            // Competences
            Route::get('users/{user}/competences', [UserCompetenceApiController::class, 'index']);
            Route::post('users/{user}/competences', [UserCompetenceApiController::class, 'sync']);

            // Lab Identity & Digital Certificate (X.509 / ICP-Brasil)
            Route::get('lab-identity', [LabIdentityApiController::class, 'show']);
            Route::post('lab-identity', [LabIdentityApiController::class, 'update']);
            Route::get('certificate', [CertificateApiController::class, 'show']);
            Route::post('certificate', [CertificateApiController::class, 'store']);
            Route::delete('certificate', [CertificateApiController::class, 'destroy']);

            // Settings
            Route::get('settings', [SettingsApiController::class, 'index']);
            Route::put('settings', [SettingsApiController::class, 'update']);

            // Notifications
            Route::get('notifications', [NotificationApiController::class, 'index']);
            Route::get('notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
            Route::post('notifications/{notification}/read', [NotificationApiController::class, 'markAsRead']);
            Route::post('notifications/{notification}/mark-as-read', [NotificationApiController::class, 'markAsRead']);
            Route::post('notifications/read-all', [NotificationApiController::class, 'markAllAsRead']);

            // Logs
            Route::get('audit-logs', [AuditLogApiController::class, 'index']);
            Route::get('audit-logs/verify-chain', [AuditLogApiController::class, 'verifyChain']);
            Route::post('audit-logs/anchor', [AuditLogApiController::class, 'anchorChain']);
            Route::get('audit-logs/anchors', [AuditLogApiController::class, 'anchors']);
            Route::get('access-logs', [AccessLogApiController::class, 'index']);
        });
    });
});
