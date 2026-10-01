<?php

declare(strict_types=1);

namespace Modules\Metrology\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Notifications\CalibrationDueNotification;
use Modules\System\Models\User;

class CheckCalibrationDue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrology:check-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica instrumentos com calibração vencida ou próxima, atualiza status e notifica usuários isolados por tenant.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Iniciando verificação de vencimento de calibrações...');

        /** @var Collection<int, Tenant> $tenants */
        $tenants = Tenant::query()->get();

        if ($tenants->isEmpty()) {
            $this->processDuesForTenant(null);
            $this->info('Verificação concluída sem tenants isolados.');

            return;
        }

        foreach ($tenants as $tenant) {
            $this->info("Processando Tenant: {$tenant->name} ({$tenant->id})...");
            tenancy()->initialize($tenant);

            try {
                $this->processDuesForTenant($tenant);
            } finally {
                tenancy()->end();
            }
        }

        $this->info('Verificação diária concluída com sucesso para todos os tenants.');
    }

    private function processDuesForTenant(?Tenant $tenant): void
    {
        // 1. Auto-expirar instrumentos cuja validade já expirou
        $expiredQuery = Instrument::where('calibration_due', '<', now()->startOfDay())
            ->whereNotIn('status', [
                ItemStatus::Expired,
                ItemStatus::InCalibration,
                ItemStatus::Maintenance,
                ItemStatus::Lost,
                ItemStatus::Rejected,
                ItemStatus::Scrapped,
            ]);

        if ($tenant) {
            $expiredQuery->where('tenant_id', $tenant->id);
        }

        $expiredCount = $expiredQuery->update(['status' => ItemStatus::Expired]);

        if ($expiredCount > 0) {
            $this->info("  -> {$expiredCount} instrumento(s) expirado(s) e atualizado(s) para 'Vencido'.");
        }

        // 2. Alertas preventivos (30, 15, 7 e 0 dias)
        $intervals = [30, 15, 7, 0];

        $usersQuery = User::query();
        if ($tenant) {
            $usersQuery->where('tenant_id', $tenant->id);
        }
        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($intervals as $days) {
            $targetDate = now()->addDays($days)->format('Y-m-d');

            $instrumentQuery = Instrument::whereDate('calibration_due', $targetDate)
                ->whereIn('status', [ItemStatus::Active, ItemStatus::Expired]);

            if ($tenant) {
                $instrumentQuery->where('tenant_id', $tenant->id);
            }

            $instruments = $instrumentQuery->get();

            if ($instruments->isNotEmpty()) {
                $criticalCount = $instruments->filter(fn (Instrument $item): bool => $item->isCritical())->count();
                $criticalMsg = $criticalCount > 0 ? " ({$criticalCount} crítico(s) NR-12/NR-13/CTQ)" : '';
                $this->info("  -> {$instruments->count()} instrumento(s) vencendo em {$days} dias{$criticalMsg}. Disparando notificações...");
                Notification::send($users, new CalibrationDueNotification($instruments, $days));
            }
        }
    }
}
