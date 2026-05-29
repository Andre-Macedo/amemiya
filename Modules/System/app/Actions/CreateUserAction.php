<?php

declare(strict_types=1);

namespace Modules\System\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\System\Models\User;

/**
 * Encapsulates the logic for creating a new system user.
 */
class CreateUserAction
{
    /**
     * Executes the user creation process.
     *
     * Args:
     *     data: The validated user data.
     *
     * Returns:
     *     The newly created User model.
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            if (! empty($data['role'])) {
                $user->assignRole($data['role']);
            }

            return $user;
        });
    }
}
