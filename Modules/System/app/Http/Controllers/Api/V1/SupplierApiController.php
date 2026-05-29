<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\System\app\Actions\CreateSupplierAction;
use Modules\System\app\Actions\UpdateSupplierAction;
use Modules\System\Http\Requests\StoreSupplierRequest;
use Modules\System\Http\Requests\UpdateSupplierRequest;
use Modules\System\Http\Resources\SupplierApiResource;
use Modules\System\Models\Supplier;

/**
 * API Controller for managing system suppliers.
 */
class SupplierApiController extends Controller
{
    /**
     * Lists suppliers with filtering and pagination.
     *
     * Args:
     *     request: The request containing search and pagination parameters.
     *
     * Returns:
     *     A collection of serialized supplier records.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 20);

        return SupplierApiResource::collection($query->orderBy('name')->paginate($perPage));
    }

    /**
     * Stores a new supplier.
     *
     * Args:
     *     request: The validated store supplier request.
     *     action: The business logic for creating a supplier.
     *
     * Returns:
     *     A serialized representation of the created supplier.
     */
    public function store(StoreSupplierRequest $request, CreateSupplierAction $action): SupplierApiResource
    {
        $supplier = $action->execute($request->validated());

        return new SupplierApiResource($supplier);
    }

    /**
     * Shows details of a specific supplier.
     *
     * Args:
     *     supplier: The supplier model instance.
     *
     * Returns:
     *     A serialized supplier record.
     */
    public function show(Supplier $supplier): SupplierApiResource
    {
        return new SupplierApiResource($supplier);
    }

    /**
     * Updates an existing supplier.
     *
     * Args:
     *     request: The validated update supplier request.
     *     supplier: The supplier to update.
     *     action: The business logic for updating a supplier.
     *
     * Returns:
     *     A serialized representation of the updated supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier, UpdateSupplierAction $action): SupplierApiResource
    {
        $supplier = $action->execute($supplier, $request->validated());

        return new SupplierApiResource($supplier);
    }

    /**
     * Deletes a supplier record.
     *
     * Args:
     *     supplier: The supplier to delete.
     *
     * Returns:
     *     A success message JSON response.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
