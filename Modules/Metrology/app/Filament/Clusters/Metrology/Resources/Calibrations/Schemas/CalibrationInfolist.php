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
                            TextEntry::make('instrument.name')
                                ->label('Instrumento'),
                            TextEntry::make('calibration_date')
                                ->label('Data da Calibração')
                                ->date('d/m/Y'),
                            TextEntry::make('type')
                                ->label('Tipo')
                                ->formatStateUsing(fn(string $state): string => $state === 'internal' ? 'Interna' : 'Externa RBC'),
                            TextEntry::make('result')
                                ->label('Resultado')
                                ->formatStateUsing(fn(?string $state): string => ucfirst($state ?? 'N/A'))
                                ->badge()
                                ->color(fn(?string $state): string => match ($state) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('deviation')
                                ->label('Desvio')
                                ->suffix(' mm'),
                            TextEntry::make('uncertainty')
                                ->label('Incerteza')
                                ->suffix(' mm'),
                            TextEntry::make('performedBy.name')
                                ->label('Executado Por'),
                            TextEntry::make('notes')
                                ->label('Observações')
                                ->columnSpanFull(),
                        ])->columns(2),

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
