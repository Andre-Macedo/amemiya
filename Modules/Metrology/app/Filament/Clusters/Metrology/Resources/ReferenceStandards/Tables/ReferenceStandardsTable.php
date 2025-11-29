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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Metrology\Models\ReferenceStandard;

class ReferenceStandardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Mostra apenas Pais (Kits ou Peças Soltas), esconde os filhos
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parent_id'))
            ->defaultSort('calibration_due', 'asc')

            ->columns([
                TextColumn::make('stock_number')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Descrição')
                    ->searchable()
                    ->description(fn (ReferenceStandard $record) =>
                    $record->children()->exists()
                        ? 'Composto por ' . $record->children()->count() . ' itens'
                        : ($record->nominal_value ? "Nominal: {$record->nominal_value} {$record->unit}" : 'Item individual')
                    )
                    ->wrap(),

                // Mostra se é Kit, Régua ou Peça Única
                TextColumn::make('type_label')
                    ->label('Estrutura')
                    ->badge()
                    ->color(fn (ReferenceStandard $record) => $record->children()->exists() ? 'info' : 'gray')
                    ->formatStateUsing(fn (ReferenceStandard $record) => $record->children()->exists() ? 'KIT / JOGO' : 'PEÇA ÚNICA'),

                TextColumn::make('referenceStandardType.name')
                    ->label('Tipo')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('serial_number')
                    ->label('Nº Série')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Status calculado na hora (Visual)
                TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->state(fn (ReferenceStandard $record) =>
                    $record->calibration_due < now() ? 'VENCIDO' : 'VIGENTE'
                    )
                    ->color(fn (string $state) => match ($state) {
                        'VENCIDO' => 'danger',
                        'VIGENTE' => 'success',
                    }),

                TextColumn::make('calibration_due')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($state) => $state < now() ? 'danger' : 'success'),
            ])
            ->filters([
                SelectFilter::make('reference_standard_type_id')
                    ->label('Tipo de Padrão')
                    ->relationship('referenceStandardType', 'name'),

                Filter::make('expired')
                    ->label('Apenas Vencidos')
                    ->query(fn (Builder $query) => $query->where('calibration_due', '<', now())),
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
