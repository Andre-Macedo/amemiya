<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Defines the form schema for calibration checklist templates.
 */
class ChecklistTemplateForm
{
    /**
     * Configures the form components.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Detalhes do Template')
                        ->description('Defina o nome e a qual tipo de instrumento este checklist se aplica.')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nome do Procedimento')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Ex: Paquímetro Digital 0-150mm'),
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
                                ->label('Pontos de Medição / Passos')
                                ->relationship()
                                ->schema([
                                    Grid::make(4)->schema([
                                        TextInput::make('order')
                                            ->label('Ordem')
                                            ->numeric()
                                            ->required()
                                            ->default(1),
                                        Select::make('question_type')
                                            ->label('Tipo de Resposta')
                                            ->options([
                                                'numeric' => 'Leitura Numérica',
                                                'boolean' => 'Aprova / Reprova',
                                                'text' => 'Observação (Texto)',
                                            ])
                                            ->live()
                                            ->required(),
                                        TextInput::make('nominal_value')
                                            ->label('V. Nominal')
                                            ->numeric()
                                            ->step('0.000001')
                                            ->visible(fn (Get $get) => $get('question_type') === 'numeric'),
                                        TextInput::make('criteria')
                                            ->label('Tolerância (+/-)')
                                            ->numeric()
                                            ->step('0.000001')
                                            ->placeholder('Ex: 0.02')
                                            ->visible(fn (Get $get) => $get('question_type') === 'numeric'),
                                    ]),
                                    Grid::make(2)->schema([
                                        TextInput::make('step')
                                            ->label('Descrição do Passo/Ponto')
                                            ->required()
                                            ->placeholder('Ex: Ponto de 50mm'),
                                        TextInput::make('required_readings')
                                            ->label('Nº de Leituras')
                                            ->numeric()
                                            ->default(3)
                                            ->required()
                                            ->visible(fn (Get $get) => $get('question_type') === 'numeric'),
                                    ]),
                                    Select::make('reference_standard_type_id')
                                        ->label('Padrão Requerido')
                                        ->relationship('referenceStandardType', 'name')
                                        ->searchable()
                                        ->required()
                                        ->preload()
                                        ->visible(fn (Get $get) => $get('question_type') === 'numeric'),
                                ])
                                ->orderColumn('order')
                                ->defaultItems(1)
                                ->reorderableWithDragAndDrop()
                                ->addActionLabel('Adicionar Novo Ponto')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }
}
