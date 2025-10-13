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
                TextColumn::make('instrument.name')->searchable()->sortable(),
                TextColumn::make('referenceStandards.name')->searchable(),
                TextColumn::make('calibration_date')->date()->sortable(),
                TextColumn::make('type')->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('result')->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'N/A')),
                TextColumn::make('performedBy.name')->searchable()->sortable(),
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
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
