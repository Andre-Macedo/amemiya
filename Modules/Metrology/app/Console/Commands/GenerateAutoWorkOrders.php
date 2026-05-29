<?php

namespace Modules\Metrology\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\WorkOrder;
use Modules\System\Models\Setting;

class GenerateAutoWorkOrders extends Command
{
    protected $signature = 'metrology:generate-auto-os';

    protected $description = 'Gera Ordens de Serviço automáticas para instrumentos prestes a vencer.';

    public function handle()
    {
        $this->info('Iniciando geração de OSs automáticas...');

        Tenant::all()->each(function (Tenant $tenant) {
            // Inicializa o contexto do tenant
            tenancy()->initialize($tenant);

            $autoGenerate = Setting::getValue('auto_generate_work_orders', 'false') === 'true';
            $daysBefore = (int) Setting::getValue('work_order_lead_days', 30);

            if ($autoGenerate) {
                $this->processTenant($tenant, $daysBefore);
            }

            tenancy()->end();
        });

        $this->info('Processamento concluído.');
    }

    protected function processTenant(Tenant $tenant, int $daysBefore)
    {
        $targetDate = now()->addDays($daysBefore);

        // Busca instrumentos que vencem em breve e que NÃO possuem uma OS aberta
        $instruments = Instrument::where('status', 'active')
            ->whereNotNull('calibration_due')
            ->where('calibration_due', '<=', $targetDate)
            ->whereDoesntHave('workOrders', function ($query) {
                $query->whereIn('status', ['received', 'in_queue', 'calibrating', 'scheduled']);
            })
            ->get();

        foreach ($instruments as $instrument) {
            WorkOrder::create([
                'tenant_id' => $tenant->id,
                'item_id' => $instrument->id,
                'item_type' => Instrument::class,
                'number' => 'AUTO-'.now()->format('Y').'-'.strtoupper(substr($instrument->id, -6)),
                'status' => 'scheduled',
                'customer_notes' => 'Gerada automaticamente pelo sistema (Vencimento próximo).',
                'expected_return_date' => $instrument->calibration_due,
            ]);

            $this->line("OS Automática gerada para: {$instrument->name} (Tenant: {$tenant->slug})");
        }
    }
}
