<?php

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Models\WorkOrder;
use Spatie\Activitylog\Models\Activity;

class AuditLogApiController extends Controller
{
    /**
     * Retorna o histórico de auditoria para um recurso específico.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'auditable_type' => ['required', 'string'],
            'auditable_id' => ['required', 'string'],
        ]);

        // Mapeia os tipos simplificados do frontend para as classes reais do backend
        $typeMapping = [
            'instrument' => Instrument::class,
            'standard' => ReferenceStandard::class,
            'calibration' => Calibration::class,
            'work_order' => WorkOrder::class,
        ];

        $subjectType = $typeMapping[$request->auditable_type] ?? $request->auditable_type;

        $logs = Activity::with('causer')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $request->auditable_id)
            ->latest()
            ->get();

        // Normaliza para o formato esperado pelo frontend atual
        $normalizedLogs = $logs->map(function ($log) {
            $props = $log->properties->toArray();

            return [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'user_name' => $log->causer?->name ?? 'Sistema',
                'formatted_date' => $log->created_at->format('d/m/Y H:i'),
                'old_values' => $props['old'] ?? null,
                'new_values' => $props['attributes'] ?? null,
                'causer_id' => $log->causer_id,
            ];
        });

        return response()->json($normalizedLogs);
    }
}
