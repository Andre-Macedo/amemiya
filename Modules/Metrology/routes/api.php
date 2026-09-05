<?php

use Illuminate\Support\Facades\Route;
use Modules\Metrology\Http\Controllers\Api\V1\AttachmentApiController;
use Modules\Metrology\Http\Controllers\Api\V1\CalibrationApiController;
use Modules\Metrology\Http\Controllers\Api\V1\ChecklistTemplateApiController;
use Modules\Metrology\Http\Controllers\Api\V1\DashboardApiController;
use Modules\Metrology\Http\Controllers\Api\V1\GlobalSearchController;
use Modules\Metrology\Http\Controllers\Api\V1\InstrumentApiController;
use Modules\Metrology\Http\Controllers\Api\V1\InstrumentChecklistController;
use Modules\Metrology\Http\Controllers\Api\V1\InstrumentTypeApiController;
use Modules\Metrology\Http\Controllers\Api\V1\IntermediateCheckApiController;
use Modules\Metrology\Http\Controllers\Api\V1\LogisticsApiController;
use Modules\Metrology\Http\Controllers\Api\V1\MaintenanceApiController;
use Modules\Metrology\Http\Controllers\Api\V1\MaterialApiController;
use Modules\Metrology\Http\Controllers\Api\V1\NonConformityApiController;
use Modules\Metrology\Http\Controllers\Api\V1\PublicCalibrationController;
use Modules\Metrology\Http\Controllers\Api\V1\PublicClientPortalController;
use Modules\Metrology\Http\Controllers\Api\V1\PublicInstrumentController;
use Modules\Metrology\Http\Controllers\Api\V1\ReferenceStandardApiController;
use Modules\Metrology\Http\Controllers\Api\V1\ReferenceStandardTypeApiController;
use Modules\Metrology\Http\Controllers\Api\V1\StandardImpactApiController;
use Modules\Metrology\Http\Controllers\Api\V1\SupplierAccreditationApiController;
use Modules\Metrology\Http\Controllers\Api\V1\TraceabilityController;
use Modules\Metrology\Http\Controllers\Api\V1\WorkOrderApiController;
use Modules\Metrology\Http\Controllers\CalibrationPdfController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes (No Auth)
Route::get('/public/instruments/{id}', [PublicInstrumentController::class, 'show']);
Route::get('/public/certificates/verify/{hash}', [PublicCalibrationController::class, 'show']);

// Client Portal (White-label) - Login Público
Route::post('/public/portal/login', [PublicClientPortalController::class, 'login']);

// Rotas Autenticadas do Portal do Cliente (Sanctum)
Route::middleware('auth:sanctum')->prefix('public/portal')->group(function () {
    Route::get('/me', [PublicClientPortalController::class, 'me']);
    Route::get('/certificates', [PublicClientPortalController::class, 'certificates']);
    Route::get('/certificates/{id}/download', [PublicClientPortalController::class, 'downloadCertificate']);
    Route::post('/certificates/download-zip', [PublicClientPortalController::class, 'downloadZip']);
    Route::get('/instruments', [PublicClientPortalController::class, 'instruments']);
    Route::get('/clients/{client}/certificates', [PublicClientPortalController::class, 'certificates']);
});

Route::middleware('auth:sanctum')->group(function () {

    // Global Search
    Route::get('/search', [GlobalSearchController::class, 'index']);

    // Instruments
    Route::post('/instruments/scan', [LogisticsApiController::class, 'scan']);
    Route::get('/instruments/{instrument}/label', [InstrumentApiController::class, 'label']);
    Route::get('/instruments/{instrument}/drift', [InstrumentApiController::class, 'drift']);
    Route::get('/instruments/{instrument}/lifecycle', [InstrumentApiController::class, 'lifecycle']);
    Route::get('/instruments/{instrument}/dossier', [InstrumentApiController::class, 'dossier']);
    Route::get('/instruments/{instrument}/recommendation', [InstrumentApiController::class, 'recommendation']);
    Route::post('/instruments/import', [InstrumentApiController::class, 'import']);
    Route::get('/instruments/export', [InstrumentApiController::class, 'export']);
    Route::get('/instruments/batch/labels', [InstrumentApiController::class, 'batchLabels']);
    Route::apiResource('instruments', InstrumentApiController::class);
    Route::apiResource('metrology/instruments', InstrumentApiController::class);
    Route::apiResource('instrument-types', InstrumentTypeApiController::class);
    Route::apiResource('metrology/instrument-types', InstrumentTypeApiController::class);
    Route::get('instruments/{instrument}/intermediate-checks', [IntermediateCheckApiController::class, 'index']);
    Route::post('intermediate-checks', [IntermediateCheckApiController::class, 'store']);

    Route::get('/instruments/{instrument}/checklists', [InstrumentChecklistController::class, 'index']);
    Route::get('/instruments/{instrument}/checklist', [InstrumentChecklistController::class, 'show']);
    Route::post('/instruments/checklist', [InstrumentChecklistController::class, 'store']); // Submission

    // Standards CRUD
    Route::get('/standards/export', [ReferenceStandardApiController::class, 'export']);
    Route::apiResource('standards', ReferenceStandardApiController::class);
    Route::get('/standards/{standard}/impact-analysis', [StandardImpactApiController::class, 'index']);
    Route::apiResource('reference-standard-types', ReferenceStandardTypeApiController::class);

    // Procedures (Templates) CRUD
    Route::apiResource('checklist-templates', ChecklistTemplateApiController::class);

    // Calibrations CRUD
    Route::get('/calibrations/export', [CalibrationApiController::class, 'export']);
    Route::apiResource('calibrations', CalibrationApiController::class)->except(['store']);
    Route::apiResource('metrology/calibrations', CalibrationApiController::class)->except(['store']);
    Route::post('/metrology/calibrations', [CalibrationApiController::class, 'store']);
    Route::post('/calibrations/{id}/approve', [CalibrationApiController::class, 'approve']);
    Route::post('/calibrations/{id}/reject', [CalibrationApiController::class, 'reject']);
    Route::post('/metrology/calibrations/{id}/approve', [CalibrationApiController::class, 'approve']);
    Route::post('/metrology/calibrations/{id}/reject', [CalibrationApiController::class, 'reject']);
    Route::get('/calibrations/{id}/traceability-chain', [TraceabilityController::class, 'show']);

    // Non-Conformities (NC)
    Route::get('/non-conformities/export', [NonConformityApiController::class, 'export']);
    Route::get('/non-conformities', [NonConformityApiController::class, 'index']);
    Route::get('/non-conformities/{id}', [NonConformityApiController::class, 'show']);
    Route::put('/non-conformities/{id}', [NonConformityApiController::class, 'update']);
    Route::post('/non-conformities/{id}/close', [NonConformityApiController::class, 'close']);

    Route::get('/metrology/non-conformities/export', [NonConformityApiController::class, 'export']);
    Route::get('/metrology/non-conformities', [NonConformityApiController::class, 'index']);
    Route::get('/metrology/non-conformities/{id}', [NonConformityApiController::class, 'show']);
    Route::put('/metrology/non-conformities/{id}', [NonConformityApiController::class, 'update']);
    Route::post('/metrology/non-conformities/{id}/close', [NonConformityApiController::class, 'close']);

    // Utils
    Route::get('/calibrations/{calibration}/pdf', [CalibrationPdfController::class, 'download'])->name('calibrations.pdf');
    Route::get('/calibrations/{calibration}/export', [CalibrationApiController::class, 'export'])->name('calibrations.export');
    Route::get('/instruments/{instrument}/label', [InstrumentApiController::class, 'label'])->name('instruments.label');
    Route::get('/checklists/{checklistTemplate}', [InstrumentChecklistController::class, 'show']);
    Route::get('/metrology/options', [CalibrationApiController::class, 'options']);
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats']);

    // Materials
    Route::apiResource('materials', MaterialApiController::class);
    Route::apiResource('metrology/materials', MaterialApiController::class);

    // Maintenance
    Route::get('maintenance', [MaintenanceApiController::class, 'index']);
    Route::post('maintenance', [MaintenanceApiController::class, 'store']);
    Route::get('maintenance/{maintenance}', [MaintenanceApiController::class, 'show']);

    // Work Orders
    Route::apiResource('work-orders', WorkOrderApiController::class);
    Route::apiResource('metrology/work-orders', WorkOrderApiController::class);

    // Supplier Accreditations
    Route::get('suppliers/{supplier}/accreditations', [SupplierAccreditationApiController::class, 'index']);
    Route::post('suppliers/{supplier}/accreditations', [SupplierAccreditationApiController::class, 'sync']);
    Route::get('suppliers/{supplier}/check-accreditation/{instrumentType}', [SupplierAccreditationApiController::class, 'check']);
    Route::get('metrology/suppliers/{supplier}/accreditations', [SupplierAccreditationApiController::class, 'index']);
    Route::post('metrology/suppliers/{supplier}/accreditations', [SupplierAccreditationApiController::class, 'sync']);
    Route::get('metrology/suppliers/{supplier}/check-accreditation/{instrumentType}', [SupplierAccreditationApiController::class, 'check']);

    // Attachments
    Route::post('attachments', [AttachmentApiController::class, 'store']);
    Route::delete('attachments/{attachment}', [AttachmentApiController::class, 'destroy']);
    Route::post('metrology/attachments', [AttachmentApiController::class, 'store']);
    Route::delete('metrology/attachments/{attachment}', [AttachmentApiController::class, 'destroy']);
});
