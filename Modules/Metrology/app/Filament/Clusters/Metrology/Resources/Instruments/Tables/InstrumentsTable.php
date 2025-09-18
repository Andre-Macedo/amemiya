<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class InstrumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('serial_number')
                    ->searchable(),
                TextColumn::make('type')
                    ->formatStateUsing(fn($state) => ucfirst($state)),
                TextColumn::make('precision')
                    ->formatStateUsing(fn($state) => ucfirst($state)),
                TextColumn::make('location'),
                TextColumn::make('acquisition_date')
                    ->date(),
                TextColumn::make('calibration_due')
                    ->date()
                    ->color(fn($record) => Carbon::parse($record->calibration_due)->isPast() ? 'danger' : null),
                TextColumn::make('status')
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Ativo',
                        'in_calibration' => 'Em Calibração',
                        'expired' => 'Vencido',
                    ]),
                TrashedFilter::make(),

            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
