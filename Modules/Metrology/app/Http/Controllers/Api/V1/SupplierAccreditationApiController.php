<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\Models\Supplier;

class SupplierAccreditationApiController extends Controller
{
    /**
     * List all accredited instrument types for a supplier.
     */
    public function index(Supplier $supplier): JsonResponse
    {
        $accreditations = $supplier->accreditedInstrumentTypes()->get()->map(function ($type) {
            return [
                'instrument_type_id' => $type->id,
                'instrument_type_name' => $type->name,
                'range' => $type->pivot->range,
                'uncertainty' => $type->pivot->uncertainty,
            ];
        });

        return response()->json($accreditations);
    }

    /**
     * Sync the accreditation scope for a supplier.
     */
    public function sync(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'accreditations' => ['required', 'array'],
            'accreditations.*.instrument_type_id' => ['required', 'exists:instrument_types,id'],
            'accreditations.*.range' => ['nullable', 'string', 'max:255'],
            'accreditations.*.uncertainty' => ['nullable', 'string', 'max:255'],
        ]);

        $syncData = [];
        foreach ($validated['accreditations'] as $item) {
            $syncData[$item['instrument_type_id']] = [
                'range' => $item['range'] ?? null,
                'uncertainty' => $item['uncertainty'] ?? null,
            ];
        }

        $supplier->accreditedInstrumentTypes()->sync($syncData);

        return response()->json(['message' => 'Supplier accreditation scope updated successfully.']);
    }

    /**
     * Check if a supplier is accredited for a specific instrument type.
     */
    public function check(Supplier $supplier, int $instrumentTypeId): JsonResponse
    {
        $isAccredited = $supplier->accreditedInstrumentTypes()
            ->where('instrument_types.id', $instrumentTypeId)
            ->exists();

        return response()->json([
            'is_accredited' => $isAccredited,
            'supplier_name' => $supplier->name,
        ]);
    }
}
