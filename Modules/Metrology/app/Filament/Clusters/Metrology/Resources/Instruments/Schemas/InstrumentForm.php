<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Instrument;

/**
 * Defines the form schema for metrology instruments in Filament.
 */
class InstrumentForm
{
    /**
     * Configures the form components.
     *
     * Args:
     *     schema: The base Filament schema to configure.
     *
     * Returns:
     *     The configured schema with all form fields.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    Grid::make(1)
                        ->schema([
                            Section::make('Identificação do Instrumento')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Nome')
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('instrument_type_id')
                                        ->label('Tipo de Instrumento')
                                        ->relationship('instrumentType', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm([
                                            TextInput::make('name')->required()->unique(),
                                        ]),
                                    Select::make('current_supplier_id')
                                        ->label('Fabricante')
                                        ->relationship('currentSupplier', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Selecione o fabricante a partir da lista de fornecedores.'),
                                    TextInput::make('serial_number')
                                        ->label('Número de Série')
                                        ->required()
                                        ->unique(Instrument::class, 'serial_number', ignoreRecord: true)
                                        ->maxLength(255),
                                    TextInput::make('stock_number')
                                        ->label('Código Interno (TAG)')
                                        ->required()
                                        ->unique(ignoreRecord: true),
                                ])->columns(2),

                        ])
                        ->columnSpan(2),

                    Grid::make(1)
                        ->schema([
                            Section::make('Imagem')
                                ->schema([
                                    FileUpload::make('image_path')
                                        ->hiddenLabel()
                                        ->directory('instrument_images')
                                        ->image()->imageEditor(),
                                ]),
                        ])
                        ->columnSpan(1),

                    Grid::make(1)
                        ->schema([
                            Section::make('Detalhes Técnicos e Datas')
                                ->schema([
                                    TextInput::make('mpe_value')
                                        ->label('Critério de Aceitação (MPE)')
                                        ->numeric()
                                        ->step('0.000001')
                                        ->required()
                                        ->placeholder('Ex: 0.02')
                                        ->helperText('Valor numérico do Erro Máximo Permissível.'),

                                    TextInput::make('mpe')
                                        ->label('Descrição do Critério (Label)')
                                        ->placeholder('Ex: 0.02 mm')
                                        ->helperText('Como o valor deve aparecer no certificado.'),

                                    TextInput::make('guard_band_multiplier_override')
                                        ->label('Override de Multiplicador (w)')
                                        ->numeric()
                                        ->placeholder('Padrão do Tipo')
                                        ->helperText('Deixe vazio para usar o valor padrão definido no Tipo de Instrumento.'),

                                    Grid::make(2)->schema([
                                        TextInput::make('measuring_range')->label('Faixa de Medição (ex: 0-150mm)'),
                                        TextInput::make('resolution')
                                            ->label('Resolução (Menor Divisão)')
                                            ->placeholder('Ex: 0.01')
                                            ->helperText('O menor valor que o instrumento consegue indicar.'),
                                    ]),
                                    TextInput::make('location')->label('Localização / Setor')->maxLength(255),
                                    DatePicker::make('acquisition_date')->label('Data de Aquisição')->required(),
                                    DatePicker::make('calibration_due')
                                        ->label('Próxima Calibração')
                                        ->required()
                                        ->helperText('Calculado automaticamente, mas pode ser ajustado.'),
                                    Select::make('status')->label('Status')
                                        ->options(ItemStatus::class)
                                        ->default(ItemStatus::Active)
                                        ->required(),
                                    RichEditor::make('notes')->label('Observações Adicionais')
                                        ->columnSpanFull(),

                                ])->columns(2),
                        ])->columnSpan(3),
                ])->columnSpanFull(),
            ]);
    }
}
