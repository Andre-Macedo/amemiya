<?php

declare(strict_types=1);

namespace Modules\System\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\System\Models\User;

/**
 * Encapsulates the logic for updating an existing system user.
 */
class UpdateUserAction
{
    /**
     * Executes the user update process.
     *
     * Args:
     *     user: The user model to update.
     *     data: The validated user data.
     *
     * Returns:
     *     The updated User model.
     */
    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->name = $data['name'];
            $user->email = $data['email'];

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            if (isset($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            return $user;
        });
    }
}
