<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\IoT\Database\Seeders\IoTDatabaseSeeder;
use Modules\Metrology\Database\Seeders\MetrologyDatabaseSeeder;
use Modules\System\Database\Seeders\SystemDatabaseSeeder;
use Modules\System\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Garantir que o Role super-admin existe
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // 1. Plano Base
        $plan = Plan::firstOrCreate(
            ['slug' => 'industrial-unlimited'],
            [
                'name' => 'Plano Industrial Ilimitado',
                'price' => 2000.00,
                'max_instruments' => 0, // Ilimitado
                'max_users' => 0,       // Ilimitado
            ]
        );

        // 2. Tenant Padrão
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'amemiya'],
            [
                'name' => 'Amemiya Indústria',
                'plan_id' => $plan->id,
                'status' => 'active',
            ]
        );

        // 3. Assinatura Ativa
        Subscription::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'default'],
            [
                'plan_id' => $plan->id,
                'gateway' => 'manual',
                'status' => 'active',
                'starts_at' => now(),
            ]
        );

        // 4. Usuário Administrador Tenant
        $user = User::updateOrCreate(
            ['email' => 'admin@amemiya.com'],
            [
                'name' => 'Administrador Amemiya',
                'password' => Hash::make('12345678'),
                'tenant_id' => $tenant->id,
                'terms_accepted_at' => now(),
                'privacy_policy_accepted_at' => now(),
            ]
        );

        $user->syncRoles(['super-admin']);

        // 5. Inicializa o Tenancy para os seeders modulares
        tenancy()->initialize($tenant);

        $this->call([
            MetrologyDatabaseSeeder::class,
            SystemDatabaseSeeder::class,
            IoTDatabaseSeeder::class,
        ]);

        tenancy()->end();

        // 6. Usuário Super Admin Central (Sem Tenant)
        $superAdmin = User::updateOrCreate(
            ['email' => 'super@admin.com'],
            [
                'name' => 'Super Admin Global',
                'password' => Hash::make('admin123'),
                'tenant_id' => null, // Global
                'terms_accepted_at' => now(),
                'privacy_policy_accepted_at' => now(),
            ]
        );

        $superAdmin->syncRoles(['super-admin']);
    }
}
