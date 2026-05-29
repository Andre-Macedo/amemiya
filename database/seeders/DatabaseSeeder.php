<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Metrology\Database\Seeders\MetrologyDatabaseSeeder;
use Modules\System\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Garantir que o Role super-admin existe (agora usando nosso modelo com HasUlids)
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // 1. Criar um Plano Base
        $plan = Plan::create([
            'name' => 'Plano Industrial Ilimitado',
            'slug' => 'industrial-unlimited',
            'price' => 2000.00,
            'max_instruments' => 0, // Ilimitado
            'max_users' => 0,       // Ilimitado
        ]);

        // 2. Criar um Tenant de Exemplo
        $tenant = Tenant::create([
            'name' => 'Amemiya Indústria',
            'slug' => 'amemiya',
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        // 3. Criar Assinatura Ativa para o Tenant
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'gateway' => 'manual',
            'status' => 'active',
            'name' => 'default',
            'starts_at' => now(),
        ]);

        // 4. O domínio 'amemiya.localhost' será criado automaticamente pelo Listener CreateTenantDomain

        // 3. Criar Usuário vinculado ao Tenant
        $user = User::create([
            'name' => 'Administrador Amemiya',
            'email' => 'admin@amemiya.com',
            'password' => Hash::make('12345678'),
            'tenant_id' => $tenant->id,
        ]);

        $user->assignRole('super-admin');

        // Inicializa o Tenancy manualmente para o restante do seeder (Metrologia)
        tenancy()->initialize($tenant);

        $this->call(MetrologyDatabaseSeeder::class);

        tenancy()->end();

        // 4. Usuário Super Admin Central (Sem Tenant)
        $superAdmin = User::create([
            'name' => 'Super Admin Global',
            'email' => 'super@admin.com',
            'password' => Hash::make('admin123'),
            'tenant_id' => null, // Global
        ]);

        $superAdmin->assignRole('super-admin');
    }
}
