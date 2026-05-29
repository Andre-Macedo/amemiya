<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Metrology\Actions\CloseNonConformityAction;
use Modules\Metrology\Exports\NonConformitiesExport;
use Modules\Metrology\Http\Requests\UpdateNonConformityRequest;
use Modules\Metrology\Http\Resources\NonConformityApiResource;
use Modules\Metrology\Models\NonConformity;

/**
 * API Controller for managing Non-Conformity Reports (RNC).
 */
class NonConformityApiController extends Controller
{
    /**
     * Lists non-conformities with status filtering and pagination.
     *
     * Args:
     *     request: The request containing filtering (status) and pagination.
     *
     * Returns:
     *     A collection of serialized non-conformities.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = NonConformity::with(['item', 'calibration', 'opener']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 20);

        return NonConformityApiResource::collection($query->latest()->paginate($perPage));
    }

    /**
     * Shows a specific non-conformity report.
     *
     * Args:
     *     id: The identifier of the NC report.
     *
     * Returns:
     *     A serialized representation of the non-conformity.
     */
    public function show(string|int $id): NonConformityApiResource
    {
        $nc = NonConformity::with(['item', 'calibration', 'opener', 'closer'])->findOrFail($id);

        return new NonConformityApiResource($nc);
    }

    /**
     * Updates an existing non-conformity report.
     *
     * Args:
     *     request: The validated update data.
     *     id: The identifier of the NC to update.
     *
     * Returns:
     *     The updated non-conformity record.
     */
    public function update(UpdateNonConformityRequest $request, string|int $id): NonConformityApiResource
    {
        $nc = NonConformity::findOrFail($id);
        $nc->update($request->validated());

        return new NonConformityApiResource($nc);
    }

    /**
     * Closes an NC report using specific business logic.
     *
     * Args:
     *     id: The identifier of the NC to close.
     *     action: The business logic for closing NCs.
     *
     * Returns:
     *     The closed non-conformity record.
     */
    public function close(string|int $id, CloseNonConformityAction $action): JsonResponse|NonConformityApiResource
    {
        try {
            $nc = NonConformity::findOrFail($id);
            $action->execute($nc);

            return new NonConformityApiResource($nc);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Exports non-conformities to an Excel file.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['status', 'priority']);

        return (new NonConformitiesExport($filters))
            ->download('rnc_report_'.now()->format('Y-m-d').'.xlsx');
    }
}
