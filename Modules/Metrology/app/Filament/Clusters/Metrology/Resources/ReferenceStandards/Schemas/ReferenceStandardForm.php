<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                                    ->relationship('referenceStandardType', 'name')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if (!$state) return;
                                        $type = \Modules\Metrology\Models\ReferenceStandardType::find($state);
                                        if ($type) {
                                            $set('is_kit', $type->is_kit);
                                            // Se virou Kit, limpa o pai (Kit não tem pai)
                                            if ($type->is_kit) $set('parent_id', null);
                                        }
                                    })
                                    ->required(),

                                // 2. Flag Manual (caso queira criar um kit customizado)
                                Toggle::make('is_kit')
                                    ->label('É um Kit/Jogo/Régua?')
                                    ->helperText('Habilita a adição de peças filhas.')
                                    ->live(),

                                // 3. Filtro Inteligente: Só mostra Kits na lista de Pais
                                Select::make('parent_id')
                                    ->label('Pertence ao Kit/Jogo/Regua')
                                    ->relationship('parent', 'name', fn ($query) => $query->where('is_kit', true))
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get) => ! $get('is_kit')) // Se eu sou um Kit, não tenho pai
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
                                    ])->columns(2)->visible(fn (Get $get) => !$get('parent_id') || $get('is_kit')),
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
                                    ->options(\Modules\Metrology\Enums\ItemStatus::class)
                                    ->default(\Modules\Metrology\Enums\ItemStatus::Active)
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
