<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Metrology\Actions\CreateMaterialAction;
use Modules\Metrology\Actions\UpdateMaterialAction;
use Modules\Metrology\Http\Requests\StoreMaterialRequest;
use Modules\Metrology\Http\Requests\UpdateMaterialRequest;
use Modules\Metrology\Http\Resources\MaterialApiResource;
use Modules\Metrology\Models\Material;

/**
 * API Controller for managing materials used for thermal expansion correction.
 */
class MaterialApiController extends Controller
{
    /**
     * Lists all available materials.
     *
     * Returns:
     *     A collection of serialized materials with their CTE values.
     */
    public function index(): AnonymousResourceCollection
    {
        return MaterialApiResource::collection(Material::all());
    }

    /**
     * Stores a new material.
     *
     * Args:
     *     request: The validated request.
     *     action: The action to create the material.
     *
     * Returns:
     *     A serialized representation of the created material.
     */
    public function store(StoreMaterialRequest $request, CreateMaterialAction $action): MaterialApiResource
    {
        $material = $action->execute($request->validated());

        return new MaterialApiResource($material);
    }

    /**
     * Shows a specific material.
     *
     * Args:
     *     material: The material to show.
     *
     * Returns:
     *     A serialized material.
     */
    public function show(Material $material): MaterialApiResource
    {
        return new MaterialApiResource($material);
    }

    /**
     * Updates a specific material.
     *
     * Args:
     *     request: The validated request.
     *     material: The material to update.
     *     action: The action to update the material.
     *
     * Returns:
     *     A serialized representation of the updated material.
     */
    public function update(UpdateMaterialRequest $request, Material $material, UpdateMaterialAction $action): MaterialApiResource
    {
        $material = $action->execute($material, $request->validated());

        return new MaterialApiResource($material);
    }

    /**
     * Deletes a specific material.
     *
     * Args:
     *     material: The material to delete.
     *
     * Returns:
     *     A JSON response with a success message.
     */
    public function destroy(Material $material): JsonResponse
    {
        $material->delete();

        return response()->json(['message' => 'Material deleted successfully.']);
    }
}
