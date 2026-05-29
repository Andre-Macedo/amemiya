<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\System\app\Actions\CreateUserAction;
use Modules\System\app\Actions\UpdateUserAction;
use Modules\System\Exports\UsersExport;
use Modules\System\Http\Requests\StoreUserRequest;
use Modules\System\Http\Requests\UpdateUserRequest;
use Modules\System\Http\Resources\UserApiResource;
use Modules\System\Models\User;
use Spatie\Permission\Models\Role;

/**
 * API Controller for managing system users.
 */
class UserApiController extends Controller
{
    /**
     * Lists users with filtering and pagination.
     *
     * Args:
     *     request: The request containing search and pagination parameters.
     *
     * Returns:
     *     A collection of serialized user records.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 20);

        return UserApiResource::collection($query->orderBy('name')->paginate($perPage));
    }

    /**
     * Stores a new user.
     *
     * Args:
     *     request: The validated store user request.
     *     action: The business logic for creating a user.
     *
     * Returns:
     *     A serialized representation of the created user.
     */
    public function store(StoreUserRequest $request, CreateUserAction $action): UserApiResource
    {
        $user = $action->execute($request->validated());

        return new UserApiResource($user);
    }

    /**
     * Shows details of a specific user.
     *
     * Args:
     *     user: The user model instance.
     *
     * Returns:
     *     A serialized user record.
     */
    public function show(User $user): UserApiResource
    {
        return new UserApiResource($user->load('roles'));
    }

    /**
     * Updates an existing user.
     *
     * Args:
     *     request: The validated update user request.
     *     user: The user to update.
     *     action: The business logic for updating a user.
     *
     * Returns:
     *     A serialized representation of the updated user.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): UserApiResource
    {
        $user = $action->execute($user, $request->validated());

        return new UserApiResource($user);
    }

    /**
     * Deletes a user record.
     *
     * Args:
     *     user: The user to delete.
     *
     * Returns:
     *     A success message JSON response.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Exports users to an Excel file.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search']);

        return (new UsersExport($filters))
            ->download('users_report_'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Lists all available roles.
     *
     * Returns:
     *     A JSON response with role names.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::pluck('name');

        return response()->json($roles);
    }
}
