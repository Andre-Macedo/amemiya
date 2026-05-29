<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;

class TenantsChart extends ChartWidget
{
    protected ?string $heading = 'Crescimento de Clientes (Tenants)';

    protected function getData(): array
    {
        // Se o pacote flowframe/laravel-trend não estiver instalado, faremos uma query simples.
        // Vou assumir que não está e fazer via Eloquent puro para evitar erros de dependência agora.

        $data = Tenant::selectRaw('COUNT(*) as count, DATE_FORMAT(created_at, "%Y-%m") as month')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Novos Clientes',
                    'data' => $data->pluck('count')->toArray(),
                    'fill' => 'start',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
