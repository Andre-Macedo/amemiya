<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Tables;

use Filament\Actions\Action;
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
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;

class CalibrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('calibratedItem.name')
                ->label('Item Calibrado')
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHasMorph('calibratedItem', [Instrument::class, ReferenceStandard::class], function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('calibrated_item_type')
                    ->label('Tipo de Item')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)) // Mostra 'Instrument' ou 'ReferenceStandard'
                    ->badge()
                    ->color(fn (string $state): string => $state === Instrument::class ? 'info' : 'warning'),

                TextColumn::make('calibration_date')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('type')->label('Tipo Cal.')
                    ->formatStateUsing(fn (string $state): string => $state === 'internal' ? 'Interna' : 'Externa')
                    ->badge(),
                TextColumn::make('result')->label('Resultado')
                    ->formatStateUsing(fn (?string $state): string => match($state) {'approved'=>'Aprovado','rejected'=>'Reprovado', default => 'N/A'})
                    ->badge()->color(fn(?string $state) => match($state){'approved'=>'success','rejected'=>'danger',default=>'gray'}),
                TextColumn::make('performedBy.name')->label('Executado Por')->searchable()->sortable(),            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('type')->label('Tipo Calibração')
                    ->options(['internal' => 'Interna', 'external_rbc' => 'Externa']),
                SelectFilter::make('result')->label('Resultado')
                    ->options(['approved' => 'Aprovado', 'rejected' => 'Reprovado']),
                SelectFilter::make('calibrated_item_type')->label('Tipo de Item')
                    ->options([
                        Instrument::class => 'Instrumento',
                        ReferenceStandard::class => 'Padrão de Referência',
                    ]),
                ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('pdf')
                        ->label('Certificado')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (Calibration $record) => route('calibration.certificate.download', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (Calibration $record) => $record->type === 'internal' && $record->result === 'approved'),
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
