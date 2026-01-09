<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ListRecords;
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
            ->modifyQueryUsing(function (Builder $query) use ($table) {
                // Pega o componente que está renderizando a tabela
                $livewire = $table->getLivewire();

                // Se for a Página de Listagem Principal (ListReferenceStandards), aplica o filtro.
                // Se for um Relation Manager, o $livewire será outra classe e o filtro NÃO será aplicado.
                if ($livewire instanceof ListRecords) {
                    $query->whereNull('parent_id');
                }
            })
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
                // Status standardized
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

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
