<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\AccessLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccessLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instrument.name')
                    ->label('Instrumento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Nome')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('station.name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('action')
                    ->label('Ação')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'check_in' => 'success',
                        'check_out' => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->modal(),
                EditAction::make()->modal(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
