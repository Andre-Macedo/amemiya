<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Metrology\Exports\ReferenceStandardsExport;
use Modules\Metrology\Http\Requests\StoreReferenceStandardRequest;
use Modules\Metrology\Http\Requests\UpdateReferenceStandardRequest;
use Modules\Metrology\Http\Resources\ReferenceStandardApiResource;
use Modules\Metrology\Models\ReferenceStandard;

/**
 * API Controller for managing reference standards used in calibrations.
 */
class ReferenceStandardApiController extends Controller
{
    /**
     * Lists reference standards with filtering and pagination.
     *
     * Args:
     *     request: The request with filters (search, type).
     *
     * Returns:
     *     A collection of serialized reference standards.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ReferenceStandard::with(['referenceStandardType', 'openNonConformity']);

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('serial_number', 'like', "%{$searchTerm}%")
                    ->orWhere('id', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('reference_standard_type_id', $request->input('type'));
        }

        $perPage = (int) $request->input('per_page', 20);

        return ReferenceStandardApiResource::collection($query->paginate($perPage));
    }

    /**
     * Stores a new reference standard.
     *
     * Args:
     *     request: The validated standard data.
     *
     * Returns:
     *     A serialized representation of the created standard.
     */
    public function store(StoreReferenceStandardRequest $request): ReferenceStandardApiResource
    {
        $standard = ReferenceStandard::create($request->validated());

        return new ReferenceStandardApiResource($standard);
    }

    /**
     * Exports reference standards to an Excel file.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        return (new ReferenceStandardsExport($filters))
            ->download('standards_inventory_'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Shows detailed info for a specific standard.
     *
     * Args:
     *     standard: The reference standard model instance.
     *
     * Returns:
     *     A serialized standard record.
     */
    public function show(ReferenceStandard $standard): ReferenceStandardApiResource
    {
        $standard->load(['referenceStandardType', 'children', 'parent', 'openNonConformity', 'attachments']);

        return new ReferenceStandardApiResource($standard);
    }

    /**
     * Updates an existing reference standard.
     *
     * Args:
     *     request: The validated update data.
     *     standard: The reference standard to update.
     *
     * Returns:
     *     A serialized representation of the updated standard.
     */
    public function update(UpdateReferenceStandardRequest $request, ReferenceStandard $standard): ReferenceStandardApiResource
    {
        $standard->update($request->validated());

        return new ReferenceStandardApiResource($standard);
    }

    /**
     * Deletes a reference standard.
     *
     * Args:
     *     standard: The standard to remove.
     *
     * Returns:
     *     A success message.
     */
    public function destroy(ReferenceStandard $standard): JsonResponse
    {
        $standard->delete();

        return response()->json(['message' => 'Standard deleted successfully']);
    }
}
