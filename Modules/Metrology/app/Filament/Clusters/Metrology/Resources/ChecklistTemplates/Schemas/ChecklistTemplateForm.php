<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

class ChecklistTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Detalhes do Template')
                        ->description('Defina o nome e a qual tipo de instrumento este checklist se aplica.')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nome do Checklist')
                                ->required()
                                ->maxLength(255),
                            Select::make('instrument_type_id')
                                ->label('Tipo de Instrumento')
                                ->relationship('instrumentType', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])->columns(2),

                    Wizard\Step::make('Itens do Checklist')
                        ->description('Adicione os passos a serem seguidos durante a calibração.')
                        ->schema([
                            Repeater::make('items')
                                ->label('Passos')
                                ->relationship()
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('order')
                                            ->label('Ordem')
                                            ->numeric()
                                            ->required()
                                            ->default(1),
                                        Select::make('question_type')
                                            ->label('Tipo de Resposta')
                                            ->options([
                                                'boolean' => 'Sim / Não',
                                                'numeric' => 'Leitura Numérica',
                                                'text' => 'Anotação (Texto)',
                                            ])
                                            ->live()
                                            ->required(),
                                        TextInput::make('required_readings')
                                            ->label('Nº de Leituras')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->visible(fn (Get $get) => $get('question_type') === 'numeric'),
                                    ]),
                                    Textarea::make('step')
                                        ->label('Descrição do Passo')
                                        ->required()
                                        ->columnSpanFull(),
                                    Select::make('reference_standard_type_id')
                                        ->label('Tipo de Padrão de Referência')
                                        ->relationship('referenceStandardType', 'name')
                                        ->searchable()
                                        ->required()
                                        ->preload()
                                        ->createOptionForm([
                                            TextInput::make('name')->required()->unique(),
                                        ])
                                        ->visible(fn (Get $get) => $get('question_type') === 'numeric'),
                                ])
                                ->orderColumn('order')
                                ->defaultItems(1)
                                ->reorderableWithDragAndDrop()
                                ->addActionLabel('Adicionar Passo')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }
}
