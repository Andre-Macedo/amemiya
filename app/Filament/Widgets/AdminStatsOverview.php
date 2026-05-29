<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;

class AdminStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Total de Tenants
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();

        // 2. Faturamento Estimado (Soma dos preços dos planos das assinaturas ativas)
        $mrr = Subscription::where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');

        // 3. Volume de Dados Global (Sem escopo de tenant para o Super Admin)
        // Nota: Como os modelos usam o Global Scope 'tenant', precisamos removê-lo para ver o total global.
        $totalInstruments = Instrument::withoutGlobalScope('tenant')->count();
        $totalCalibrations = Calibration::withoutGlobalScope('tenant')->count();

        return [
            Stat::make('Clientes Ativos', "{$activeTenants} / {$totalTenants}")
                ->description('Total de empresas na plataforma')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('MRR Estimado', 'R$ '.number_format($mrr, 2, ',', '.'))
                ->description('Receita Recorrente Mensal')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Ativos Monitorados', number_format($totalInstruments, 0, ',', '.'))
                ->description('Total de instrumentos cadastrados')
                ->descriptionIcon('heroicon-m-wrench-screwdriver'),

            Stat::make('Calibrações Realizadas', number_format($totalCalibrations, 0, ',', '.'))
                ->description('Total de registros históricos')
                ->descriptionIcon('heroicon-m-clipboard-document-check'),
        ];
    }
}
