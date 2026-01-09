<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Widgets;

use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\Metrology\Models\Instrument;

class UpcomingCalibrations extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Instrument::query()
                    ->where('calibration_due', '<=', now()->addDays(30))
                    ->where('status', '!=', \Modules\Metrology\Enums\ItemStatus::Scrapped)
                    ->orderBy('calibration_due')
            )
            ->heading('Próximas Calibrações (30 Dias)')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Instrumento')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock_number')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('calibration_due')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record->calibration_due < now() ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('location')
                    ->label('Localização'),
            ])
            ->actions([
                Action::make('view')
                    ->label('Ver')
                    ->url(fn (Instrument $record): string => \Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages\ViewInstrument::getUrl(['record' => $record])),
            ]);
    }
}
