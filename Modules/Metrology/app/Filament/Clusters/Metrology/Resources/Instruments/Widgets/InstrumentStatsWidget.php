<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Metrology\Models\Instrument;

class InstrumentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Instrumentos', Instrument::count())
                ->description('Todos os instrumentos registados')
                ->color('black'),
            Stat::make('Instrumentos Ativos', Instrument::where('status', 'active')->count())
                ->description('Instrumentos prontos para uso')
                ->color('success'),
            Stat::make('Calibrações Vencidas', Instrument::where('status', 'expired')->count())
                ->description('Precisam de atenção imediata')
                ->color('danger')
                ->icon('heroicon-m-exclamation-triangle'),

            Stat::make('Em Calibração', Instrument::where('status', 'in_calibration')->count())
                ->description('Instrumentos em processo de calibração')
                ->color('warning'),
        ];
    }
}
