<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReferenceStandardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('serial_number')->searchable(),
                TextColumn::make('type'),
                TextColumn::make('calibration_date')->date(),
                TextColumn::make('calibration_due')->date()->color(fn($record) => Carbon::parse($record->calibration_due)->isPast() ? 'danger' : null),
                TextColumn::make('traceability'),

            ])
            ->filters([
                SelectFilter::make('type')->options(['bloco_padrao' => 'Bloco Padrão', 'calibrador' => 'Calibrador', 'peso_padrao' => 'Peso Padrão']),

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
