<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CalibrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instrument.name')
                    ->searchable(),
                TextColumn::make('referenceStandards.name')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->searchable(),
                TextColumn::make('calibration_date')
                    ->date(),
                TextColumn::make('type')
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('result')
                    ->formatStateUsing(fn($state) => ucfirst($state ?? '')),
                TextColumn::make('deviation')
                    ->suffix(' mm'),
                TextColumn::make('uncertainty')
                    ->suffix(' mm'),
//                TextColumn::make('performedBy.name'),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('type')
                    ->options([
                        'internal' => 'Interna',
                        'external_rbc' => 'Externa RBC',
                    ]),
                SelectFilter::make('result')
                    ->options([
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ])
            ]);
    }
}
