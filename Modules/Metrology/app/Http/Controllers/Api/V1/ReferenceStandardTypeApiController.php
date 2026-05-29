<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Metrology\Http\Resources\ReferenceStandardTypeApiResource;
use Modules\Metrology\Models\ReferenceStandardType;

/**
 * API Controller for managing types/classifications of reference standards.
 */
class ReferenceStandardTypeApiController extends Controller
{
    /**
     * Lists all available standard types.
     *
     * Returns:
     *     A collection of serialized standard types.
     */
    public function index(): AnonymousResourceCollection
    {
        return ReferenceStandardTypeApiResource::collection(ReferenceStandardType::all());
    }

    /**
     * Stores a new reference standard type.
     *
     * Args:
     *     request: The validated request data.
     *
     * Returns:
     *     The created record.
     */
    public function store(Request $request): ReferenceStandardTypeApiResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $type = ReferenceStandardType::create($validated);

        return new ReferenceStandardTypeApiResource($type);
    }

    /**
     * Shows a specific reference standard type.
     *
     * Args:
     *     referenceStandardType: The model instance.
     *
     * Returns:
     *     The serialized record.
     */
    public function show(ReferenceStandardType $referenceStandardType): ReferenceStandardTypeApiResource
    {
        return new ReferenceStandardTypeApiResource($referenceStandardType);
    }

    /**
     * Updates an existing reference standard type.
     *
     * Args:
     *     request: The validated request data.
     *     referenceStandardType: The model instance to update.
     *
     * Returns:
     *     The updated record.
     */
    public function update(Request $request, ReferenceStandardType $referenceStandardType): ReferenceStandardTypeApiResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $referenceStandardType->update($validated);

        return new ReferenceStandardTypeApiResource($referenceStandardType);
    }

    /**
     * Deletes a reference standard type.
     *
     * Args:
     *     referenceStandardType: The model instance to remove.
     *
     * Returns:
     *     A no-content response.
     */
    public function destroy(ReferenceStandardType $referenceStandardType): JsonResponse
    {
        $referenceStandardType->delete();

        return response()->json(null, 204);
    }
}
