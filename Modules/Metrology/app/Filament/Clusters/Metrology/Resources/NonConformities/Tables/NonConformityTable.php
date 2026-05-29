<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformities\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Table configuration for Non-Conformity reports.
 */
class NonConformityTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger',
                        'investigating' => 'warning',
                        'resolved' => 'info',
                        'closed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Aberta',
                        'investigating' => 'Investigando',
                        'resolved' => 'Resolvida',
                        'closed' => 'Fechada',
                        default => $state,
                    }),
                TextColumn::make('priority')
                    ->label('Prioridade')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('item.name')
                    ->label('Ativo')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Abertura')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Aberta',
                        'investigating' => 'Investigando',
                        'resolved' => 'Resolvida',
                        'closed' => 'Fechada',
                    ]),
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Baixa',
                        'medium' => 'Média',
                        'high' => 'Alta',
                        'critical' => 'Crítica',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
