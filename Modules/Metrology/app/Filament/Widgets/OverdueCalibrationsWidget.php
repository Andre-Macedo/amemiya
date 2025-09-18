<?php

namespace Modules\Metrology\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Metrology\Models\Instrument;

class OverdueCalibrationsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $overdue = Instrument::where('calibration_due', '<', '2030-01-01')->count();

        return [
            Stat::make('Overdue Calibrations', $overdue)
                ->description('Instruments needing calibration')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }
}
