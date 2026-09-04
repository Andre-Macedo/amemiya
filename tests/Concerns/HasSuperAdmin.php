<?php

namespace Tests\Concerns;

use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Modules\System\Models\User;
use Spatie\Permission\PermissionRegistrar;

trait HasSuperAdmin
{
    public function createSuperAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }

    public function assignSuperAdmin(User $user): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Gate::before(function ($user, $ability) {
            return true;
        });

        return $user;
    }
}
