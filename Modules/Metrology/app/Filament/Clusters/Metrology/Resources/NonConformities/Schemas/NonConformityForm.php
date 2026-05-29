<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformities\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form schema for Non-Conformity Reports (RNC).
 */
class NonConformityForm
{
    /**
     * Configures the NC form components.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    Grid::make(1)->schema([
                        Section::make('Identificação da Ocorrência')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Título da RNC')
                                    ->required()
                                    ->maxLength(255),
                                RichEditor::make('description')
                                    ->label('Descrição do Desvio')
                                    ->required()
                                    ->columnSpanFull(),

                                Grid::make(2)->schema([
                                    Select::make('priority')
                                        ->label('Prioridade')
                                        ->options([
                                            'low' => 'Baixa',
                                            'medium' => 'Média',
                                            'high' => 'Alta',
                                            'critical' => 'Crítica',
                                        ])
                                        ->required(),
                                    Select::make('status')
                                        ->label('Status do Tratamento')
                                        ->options([
                                            'open' => 'Aberta',
                                            'investigating' => 'Em Investigação',
                                            'resolved' => 'Aguardando Validação',
                                            'closed' => 'Fechada/Concluída',
                                        ])
                                        ->required(),
                                ]),
                            ]),
                    ])->columnSpan(2),

                    Grid::make(1)->schema([
                        Section::make('Rastreabilidade')
                            ->schema([
                                Placeholder::make('item_info')
                                    ->label('Ativo Relacionado')
                                    ->content(fn ($record) => $record?->item?->name ?? 'N/A'),
                                Placeholder::make('calibration_info')
                                    ->label('Calibração Origem')
                                    ->content(fn ($record) => $record?->calibration ? "ID #{$record->calibration->id}" : 'Abertura Manual'),
                                Placeholder::make('opened_by')
                                    ->label('Aberto Por')
                                    ->content(fn ($record) => $record?->opener?->name ?? 'Sistema'),
                                DatePicker::make('created_at')
                                    ->label('Data de Abertura')
                                    ->disabled(),
                            ]),
                    ])->columnSpan(1),

                    Section::make('Análise Técnica e Ações (ISO 9001)')
                        ->description('Preencha a análise de causa e as ações para fechamento da RNC.')
                        ->schema([
                            RichEditor::make('root_cause_analysis')
                                ->label('Análise de Causa Raiz')
                                ->placeholder('Por que o desvio ocorreu? (Use os 5 Porquês)')
                                ->columnSpanFull(),

                            Grid::make(2)->schema([
                                RichEditor::make('immediate_action')
                                    ->label('Ação Imediata (Contenção)')
                                    ->placeholder('O que foi feito na hora para conter o problema?'),
                                RichEditor::make('corrective_action')
                                    ->label('Ação Corretiva (Eliminação da Causa)')
                                    ->placeholder('O que será feito para que não ocorra novamente?'),
                            ]),

                            RichEditor::make('preventive_action')
                                ->label('Ação Preventiva / Observações de Eficácia')
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                ]),
            ]);
    }
}
