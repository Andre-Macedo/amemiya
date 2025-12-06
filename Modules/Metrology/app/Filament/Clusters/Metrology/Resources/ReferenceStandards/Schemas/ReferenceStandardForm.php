<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\Metrology\Models\ReferenceStandardType;

class ReferenceStandardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                    // COLUNA 1 & 2: Dados Principais
                    Grid::make(1)->schema([
                        Section::make('Identificação')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Descrição / Nome')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('reference_standard_type_id')
                                    ->label('Tipo de Padrão')
                                    ->options(ReferenceStandardType::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                // Dados de Identificação (Opcionais se for Filho)
                                Fieldset::make('Rastreabilidade')
                                    ->schema([
                                        TextInput::make('serial_number')
                                            ->label('Número de Série')
                                            ->placeholder(fn (Get $get) => $get('parent_id') ? 'Usa o do Kit (Pai)' : '')
                                            ->required(fn (Get $get) => $get('parent_id') === null)
                                            ->maxLength(255),

                                        TextInput::make('stock_number')
                                            ->label('Código / Patrimônio')
                                            ->placeholder(fn (Get $get) => $get('parent_id') ? 'Usa o do Kit (Pai)' : '')
                                            ->required(fn (Get $get) => $get('parent_id') === null)
                                            ->maxLength(255),
                                    ])->columns(2),
                            ]),

                        Section::make('Dados Metrológicos')
                            ->description('Preencha se este item tiver valor calibrado.')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('nominal_value')
                                        ->label('Valor Nominal')
                                        ->numeric()
                                        ->suffix('mm (ex)'), // Idealmente viria da unidade

                                    TextInput::make('actual_value')
                                        ->label('Valor Verdadeiro')
                                        ->helperText('Atualizado na calibração')
                                        ->numeric(),

                                    TextInput::make('uncertainty')
                                        ->label('Incerteza')
                                        ->numeric(),
                                ]),
                                TextInput::make('unit')
                                    ->label('Unidade')
                                    ->default('mm')
                                    ->maxLength(10),
                            ]),
                    ])->columnSpan(2),

                    // COLUNA 3: Controle e Datas
                    Grid::make(1)->schema([
                        Section::make('Controle de Validade')
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'active' => 'Ativo',
                                        'expired' => 'Vencido',
                                        'rejected' => 'Rejeitado',
                                        'maintenance' => 'Em Manutenção'
                                    ])
                                    ->default('active')
                                    ->required(),

                                DatePicker::make('calibration_due')
                                    ->label('Próximo Vencimento')
                                    ->required(), // Geralmente obrigatório para controle

                                DatePicker::make('calibration_date')
                                    ->label('Última Calibração'),
                            ]),

                        Section::make('Certificado')
                            ->schema([
                                FileUpload::make('certificate_path')
                                    ->label('Arquivo PDF')
                                    ->directory('reference-certificates')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->openable()
                                    ->downloadable(),
                            ]),
                    ])->columnSpan(1),

                Section::make('Observações')
                    ->schema([
                        Textarea::make('description')
                            ->label('')
                            ->rows(3),
                    ])->collapsed(),
            ]);
    }
}
