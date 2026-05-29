<?php

use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Models\IoTNode;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$tenant = Tenant::where('slug', 'amemiya')->first();

if (!$tenant) {
    echo "Tenant amemiya não encontrado.\n";
    exit(1);
}

// 0. Criar uma Máquina de teste (se não existir)
$machineId = (string) str()->ulid();
DB::table('machines')->updateOrInsert(
    ['name' => 'Máquina Simulada'],
    [
        'id' => $machineId,
        'tenant_id' => $tenant->id,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]
);

$machine = DB::table('machines')->where('name', 'Máquina Simulada')->first();

// 1. Criar Gateway de Teste
$gateway = IoTGateway::updateOrCreate(
    ['device_id' => 'gw_sim_001'],
    [
        'tenant_id' => $tenant->id,
        'name' => 'Gateway Simulador',
        'status' => 'online',
        'last_at' => now(),
    ]
);

// 2. Criar Nó de Teste
$node = IoTNode::updateOrCreate(
    ['gateway_id' => $gateway->id, 'node_id' => 'node_sim_001'],
    [
        'tenant_id' => $tenant->id,
        'name' => 'Motor Principal Simulado',
        'machine_id' => $machine->id,
        'status' => 'online',
    ]
);

echo "Simulação preparada:\n";
echo "Gateway Device ID: gw_sim_001\n";
echo "Node ID: node_sim_001\n";
echo "Machine ID: {$machine->id}\n";
echo "Tópico MQTT: sensors/gw_sim_001/telemetry\n";
