<?php

namespace Modules\Metrology\Filament\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Modules\Metrology\Models\Calibration;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Modules\Metrology\Models\Instrument;

class RecentCalibrationsWidget extends TableWidget
{
    protected function getTableQuery(): Builder
    {
        return Calibration::with(['calibratedItem'])
            ->where('calibrated_item_type', Instrument::class)
            ->latest('calibration_date')
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('calibratedItem.name')
                ->label('Instrumento'),
            TextColumn::make('calibration_date')
                ->date(),
            IconColumn::make('result')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle'),
        ];
    }

}
