<?php

namespace Modules\Metrology\Filament\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Modules\Metrology\Models\Calibration;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentCalibrationsWidget extends TableWidget
{
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Calibration::with(['instrument'])
            ->orderBy('calibration_date', 'desc')
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('instrument.name')
                ->label('Instrument'),
            TextColumn::make('calibration_date')
                ->date(),
            IconColumn::make('result')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle'),
        ];
    }
}
