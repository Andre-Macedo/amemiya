<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ChecklistTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Template Details')
                    ->tabs([
                        Tabs\Tab::make('Informações Gerais')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextEntry::make('name')->label('Nome do Checklist'),
                                        TextEntry::make('instrumentType.name')->label('Aplicável ao Tipo de Instrumento'),
                                        TextEntry::make('items_count')
                                            ->label('Número de Passos')
                                            ->counts('items'),
                                        TextEntry::make('created_at')
                                            ->label('Data de Criação')
                                            ->dateTime('d/m/Y H:i'),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Passos do Checklist')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->label('')
                                    ->schema([
                                        Grid::make(8)->schema([
                                            TextEntry::make('order')
                                                ->label('Ordem')
                                                ->columnSpan(1),
                                            TextEntry::make('step')
                                                ->label('Descrição do Passo')
                                                ->columnSpan(3),
                                            TextEntry::make('question_type')
                                                ->label('Tipo de Resposta')
                                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                                    'boolean' => 'Sim / Não',
                                                    'numeric' => 'Leitura Numérica',
                                                    'text' => 'Anotação',
                                                    default => $state,
                                                })->columnSpan(2),
                                            TextEntry::make('referenceStandardType.name')
                                                ->label('Padrão de Referência')
                                                ->placeholder('N/A')
                                                ->columnSpan(2),
                                        ]),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
