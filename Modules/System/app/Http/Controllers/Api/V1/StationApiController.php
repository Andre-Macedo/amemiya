<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\System\app\Actions\CreateStationAction;
use Modules\System\app\Actions\UpdateStationAction;
use Modules\System\Http\Requests\StoreStationRequest;
use Modules\System\Http\Requests\UpdateStationRequest;
use Modules\System\Http\Resources\StationApiResource;
use Modules\System\Models\Station;

/**
 * API Controller for managing system stations.
 */
class StationApiController extends Controller
{
    /**
     * Lists stations with filtering and pagination.
     *
     * Args:
     *     request: The request containing search and pagination parameters.
     *
     * Returns:
     *     A collection of serialized station records.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Station::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 20);

        return StationApiResource::collection($query->orderBy('name')->paginate($perPage));
    }

    /**
     * Stores a new station.
     *
     * Args:
     *     request: The validated store station request.
     *     action: The business logic for creating a station.
     *
     * Returns:
     *     A serialized representation of the created station.
     */
    public function store(StoreStationRequest $request, CreateStationAction $action): StationApiResource
    {
        $station = $action->execute($request->validated());

        return new StationApiResource($station);
    }

    /**
     * Shows details of a specific station.
     *
     * Args:
     *     station: The station model instance.
     *
     * Returns:
     *     A serialized station record.
     */
    public function show(Station $station): StationApiResource
    {
        return new StationApiResource($station);
    }

    /**
     * Updates an existing station.
     *
     * Args:
     *     request: The validated update station request.
     *     station: The station to update.
     *     action: The business logic for updating a station.
     *
     * Returns:
     *     A serialized representation of the updated station.
     */
    public function update(UpdateStationRequest $request, Station $station, UpdateStationAction $action): StationApiResource
    {
        $station = $action->execute($station, $request->validated());

        return new StationApiResource($station);
    }

    /**
     * Deletes a station record.
     *
     * Args:
     *     station: The station to delete.
     *
     * Returns:
     *     A success message JSON response.
     */
    public function destroy(Station $station): JsonResponse
    {
        $station->delete();

        return response()->json(['message' => 'Station deleted successfully']);
    }
}
