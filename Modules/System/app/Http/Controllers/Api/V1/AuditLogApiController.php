<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Models\WorkOrder;
use Modules\System\Services\AuditChainService;
use Spatie\Activitylog\Models\Activity;

class AuditLogApiController extends Controller
{
    /**
     * Display a listing of audit logs (global or resource-scoped).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $query = Activity::with('causer')->latest();

        if ($request->filled('auditable_type')) {
            $typeMapping = [
                'instrument' => Instrument::class,
                'standard' => ReferenceStandard::class,
                'calibration' => Calibration::class,
                'work_order' => WorkOrder::class,
            ];

            $subjectType = $typeMapping[$request->input('auditable_type')] ?? $request->input('auditable_type');
            $query->where('subject_type', $subjectType);
        }

        if ($request->filled('auditable_id')) {
            $query->where('subject_id', (string) $request->input('auditable_id'));
        }

        $paginated = $query->paginate($perPage);

        $transformed = $paginated->getCollection()->map(function (Activity $log): array {
            $props = $log->properties ? $log->properties->toArray() : [];

            return [
                'id' => (string) $log->id,
                'sequence_number' => $log->sequence_number,
                'previous_hash' => $log->previous_hash,
                'record_hash' => $log->record_hash,
                'event' => (string) ($log->event ?? 'updated'),
                'description' => (string) ($log->description ?? ''),
                'user_name' => $log->causer?->name ?? 'Sistema',
                'created_at' => $log->created_at?->toIso8601String() ?? '',
                'formatted_date' => $log->created_at?->format('d/m/Y H:i') ?? '',
                'auditable_type' => class_basename((string) ($log->subject_type ?? 'System')),
                'auditable_id' => $log->subject_id,
                'old_values' => $props['old'] ?? null,
                'new_values' => $props['attributes'] ?? null,
                'url' => null,
                'causer_id' => $log->causer_id,
            ];
        });

        return response()->json([
            'data' => $transformed,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    /**
     * Valida a integridade criptográfica da cadeia forense de auditoria.
     */
    public function verifyChain(Request $request, AuditChainService $auditChainService): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $report = $auditChainService->verifyChain($tenantId);

        return response()->json($report);
    }
}
