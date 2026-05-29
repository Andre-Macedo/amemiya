<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Metrology\Http\Requests\StoreWorkOrderRequest;
use Modules\Metrology\Http\Requests\UpdateWorkOrderRequest;
use Modules\Metrology\Http\Resources\WorkOrderApiResource;
use Modules\Metrology\Models\WorkOrder;

class WorkOrderApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = WorkOrder::with(['item', 'receivedBy']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHasMorph('item', '*', function ($itemQuery) use ($search) {
                        $itemQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->input('per_page', 20);

        return WorkOrderApiResource::collection($query->latest()->paginate($perPage));
    }

    public function store(StoreWorkOrderRequest $request): WorkOrderApiResource
    {
        $data = $request->validated();
        $data['received_by_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'received';

        $workOrder = WorkOrder::create($data);

        return new WorkOrderApiResource($workOrder->load(['item', 'receivedBy']));
    }

    public function show(WorkOrder $workOrder): WorkOrderApiResource
    {
        return new WorkOrderApiResource($workOrder->load(['item', 'receivedBy']));
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): WorkOrderApiResource
    {
        $oldStatus = $workOrder->status;
        $workOrder->update($request->validated());

        // Lógica de Movimentação Física
        if ($oldStatus !== 'received' && $workOrder->status === 'received') {
            if ($workOrder->item_type === Instrument::class && $workOrder->destination_station_id) {
                $instrument = $workOrder->item;
                $instrument->update([
                    'current_station_id' => $workOrder->destination_station_id,
                ]);
            }
        }

        return new WorkOrderApiResource($workOrder->load(['item', 'receivedBy', 'originStation', 'destinationStation']));
    }

    public function destroy(WorkOrder $workOrder): JsonResponse
    {
        $workOrder->delete();

        return response()->json(['message' => 'Work order deleted successfully.']);
    }
}
