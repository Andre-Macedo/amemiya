<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Metrology\Http\Requests\StoreInstrumentTypeRequest;
use Modules\Metrology\Http\Requests\UpdateInstrumentTypeRequest;
use Modules\Metrology\Http\Resources\InstrumentTypeApiResource;
use Modules\Metrology\Models\InstrumentType;

/**
 * API Controller for managing instrument classifications and default rules.
 */
class InstrumentTypeApiController extends Controller
{
    /**
     * Lists all available instrument types.
     *
     * Returns:
     *     A collection of serialized instrument types.
     */
    public function index(): AnonymousResourceCollection
    {
        return InstrumentTypeApiResource::collection(InstrumentType::all());
    }

    /**
     * Stores a new instrument type.
     *
     * Args:
     *     request: The validated request data.
     *
     * Returns:
     *     The created instrument type record.
     */
    public function store(StoreInstrumentTypeRequest $request): InstrumentTypeApiResource
    {
        $type = InstrumentType::create($request->validated());

        return new InstrumentTypeApiResource($type);
    }

    /**
     * Shows a specific instrument type.
     *
     * Args:
     *     instrumentType: The model instance.
     *
     * Returns:
     *     The serialized instrument type.
     */
    public function show(InstrumentType $instrumentType): InstrumentTypeApiResource
    {
        return new InstrumentTypeApiResource($instrumentType);
    }

    /**
     * Updates an existing instrument type.
     *
     * Args:
     *     request: The validated request data.
     *     instrumentType: The model instance to update.
     *
     * Returns:
     *     The updated instrument type record.
     */
    public function update(UpdateInstrumentTypeRequest $request, InstrumentType $instrumentType): InstrumentTypeApiResource
    {
        $instrumentType->update($request->validated());

        return new InstrumentTypeApiResource($instrumentType);
    }

    /**
     * Deletes an instrument type.
     *
     * Args:
     *     instrumentType: The model instance to remove.
     *
     * Returns:
     *     A no-content response.
     */
    public function destroy(InstrumentType $instrumentType): JsonResponse
    {
        $instrumentType->delete();

        return response()->json(null, 204);
    }
}
