<?php

namespace App\Filament\Clusters\System\Resources\Suppliers\Tables;

use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable()
                    ->description(fn (Supplier $record) => $record->trade_name),

                IconColumn::make('is_manufacturer')
                    ->label('Fabricante')
                    ->boolean(),

                IconColumn::make('is_calibration_provider')
                    ->label('Lab. Calibração')
                    ->boolean(),

                TextColumn::make('accreditation_valid_until')
                    ->label('Validade ISO 17025')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state < now() ? 'danger' : 'success')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('laboratories')
                    ->label('Apenas Laboratórios')
                    ->query(fn ($query) => $query->where('is_calibration_provider', true)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
