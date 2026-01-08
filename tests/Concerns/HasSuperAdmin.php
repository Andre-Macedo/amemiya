<?php

namespace Tests\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Role;

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
        
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return true;
        });

        return $user;
    }
}
