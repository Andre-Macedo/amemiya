<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\MaintenanceRecord;

class MaintenanceApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceRecord::with(['technician', 'supplier', 'instrument']);

        if ($request->filled('instrument_id')) {
            $query->where('instrument_id', $request->instrument_id);
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'instrument_id' => ['required', 'exists:instruments,id'],
            'type' => ['required', 'in:preventive,corrective,adjustment'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string'],
            'findings' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'parts_replaced' => ['nullable', 'array'],
        ]);

        $record = MaintenanceRecord::create($data);

        // Se a manutenção for de ajuste, o instrumento pode precisar de calibração imediata
        if ($data['type'] === 'adjustment' || $data['type'] === 'corrective') {
            $instrument = Instrument::find($data['instrument_id']);
            $instrument->transitionTo(ItemStatus::Maintenance);
        }

        return response()->json($record, 201);
    }

    public function show(MaintenanceRecord $maintenance): JsonResponse
    {
        return response()->json($maintenance->load(['technician', 'supplier', 'instrument']));
    }
}
