<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Modules\Metrology\Models\Calibration;

class CalibrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Detalhes da Calibração')
                ->tabs([
                    Tabs\Tab::make('Informações Gerais')
                        ->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('calibratedItem.name') // Usa a relação polimórfica
                                ->label('Item Calibrado'),
                                TextEntry::make('calibrated_item_type')
                                    ->label('Tipo de Item')
                                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                                    ->badge()
                                    ->color(fn (string $state): string => $state === Instrument::class ? 'info' : 'warning'),
                                TextEntry::make('calibration_date')->label('Data da Calibração')->date('d/m/Y'),
                                TextEntry::make('type')->label('Tipo Cal.')
                                    ->formatStateUsing(fn (string $state): string => $state === 'internal' ? 'Interna' : 'Externa RBC'),
                                TextEntry::make('result')->label('Resultado')
                                    ->formatStateUsing(fn (?string $state): string => match($state) {'approved'=>'Aprovado','rejected'=>'Reprovado', default => 'N/A'})
                                    ->badge()->color(fn(?string $state) => match($state){'approved'=>'success','rejected'=>'danger',default=>'gray'}),
                                TextEntry::make('deviation')->label('Desvio')->suffix(' mm'),
                                TextEntry::make('uncertainty')->label('Incerteza')->suffix(' mm'),
                                TextEntry::make('performedBy.name')->label('Executado Por'),
                                TextEntry::make('notes')->label('Observações')->columnSpanFull(),
                                // Adicionar link/visualização para certificado se for externo?
                                // FileEntry::make('certificate_path')->visible(fn($record)=>$record->type == 'external_rbc'),
                            ])->columnSpanFull()->contained(false),
                    ]),

                    Tabs\Tab::make('Checklist Executado')
                        ->schema([
                            RepeatableEntry::make('checklist.items')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextEntry::make('step')
                                            ->label('Passo')
                                            ->columnSpan(2),
                                        TextEntry::make('result')
                                            ->label('Resultado')
                                            ->badge()
                                            ->color(fn(string $state): string => match ($state) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                default => 'gray',
                                            }),
                                    ]),
                                    TextEntry::make('readings')
                                        ->label('Leituras')
                                        ->formatStateUsing(function ($state): string {
                                            if (is_array($state)) {
                                                return empty($state) ? 'N/A' : implode(' | ', $state);
                                            }
                                            return (string)$state;
                                        })
                                        ->visible(fn($record) => $record->question_type === 'numeric'),
                                    TextEntry::make('referenceStandard.name')
                                        ->label('Padrão de Referência Utilizado')
                                        ->placeholder('N/A')
                                        ->visible(fn($record) => $record->question_type === 'numeric'),
                                    TextEntry::make('notes')
                                        ->label('Anotações')
                                        ->visible(fn($record) => $record->question_type === 'text' && !empty($record->notes)),
                                ])
                                ->getStateUsing(function (Calibration $record) {
                                    if (!$record->checklist) {
                                        return [];
                                    }
                                    return $record->checklist->items()->with('referenceStandard')->get();
                                }),
                        ])
                        ->visible(fn($record) => $record->type === 'internal' && $record->checklist),
                ])->columnSpanFull(),
        ]);
    }
}
