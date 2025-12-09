<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\ReferenceStandardResource;
use Modules\Metrology\Models\ReferenceStandard;

class ReferenceStandardInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Padrão')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('name')->label('Descrição')->columnSpan(2),

                            TextEntry::make('referenceStandardType.name')
                                ->label('Tipo')
                                ->badge(),

                            TextEntry::make('is_kit')
                                ->label('Estrutura')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state ? 'KIT / CONJUNTO' : 'ITEM UNITÁRIO')
                                ->color(fn ($state) => $state ? 'info' : 'gray'),
                        ]),

                        Grid::make(4)->schema([
                            // Usa os dados efetivos (Calculados no Model)
                            TextEntry::make('effective_serial_number')->label('Nº Série'),
                            TextEntry::make('effective_stock_number')->label('Patrimônio'),

                            TextEntry::make('parent.name')
                                ->label('Parte do Kit')
                                ->placeholder('-')
                                ->url(fn ($record) => $record->parent_id ? route('filament.admin.metrology.resources.reference-standards.view', $record->parent_id) : null)
                                ->visible(fn ($record) => $record->parent_id),

                            TextEntry::make('status')
                                ->badge()
                                ->color(fn ($state) => match($state) {
                                    'active' => 'success',
                                    'expired' => 'danger',
                                    default => 'gray'
                                }),
                        ]),
                    ])->columnSpanFull(),

                // Seção técnica só aparece se tiver dados
                Section::make('Metrologia')
                    ->schema([
                        TextEntry::make('nominal_value')->label('Nominal')->suffix(fn($record)=>' '.($record->unit ?? 'mm')),
                        TextEntry::make('actual_value')->label('Valor Real'),
                        TextEntry::make('uncertainty')->label('Incerteza'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => !$record->is_kit),
            ]);
    }
}
