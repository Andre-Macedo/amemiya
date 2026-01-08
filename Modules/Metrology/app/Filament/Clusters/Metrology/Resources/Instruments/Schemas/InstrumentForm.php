<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Metrology\Models\Instrument;

class InstrumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    Grid::make(1)
                        ->schema([
                            Section::make('Identificação do Instrumento')
                                ->schema([
                                    TextInput::make('name')->label('Nome')->required()->maxLength(255),
                                    Select::make('instrument_type_id')
                                        ->label('Tipo de Instrumento')
                                        ->relationship('instrumentType', 'name')
                                        ->required()->searchable()->preload()
                                        ->createOptionForm([
                                            TextInput::make('name')->required()->unique(),
                                        ]),
                                    TextInput::make('manufacturer')
                                        ->label('Fabricante'),
                                    TextInput::make('serial_number')
                                        ->label('Número de Série')
                                        ->required()
                                        ->unique(Instrument::class, 'serial_number', ignoreRecord: true)
                                        ->maxLength(255),
                                    TextInput::make('stock_number')
                                        ->label('Código (Ex: PQ-001)')
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
                        Section::make('Detalhes e Datas')
                            ->schema([
                                TextInput::make('uncertainty')
                                    ->label('Erro Máximo Admissível (Critério)')
                                    ->required()
                                    ->placeholder('Ex: 0.02 mm')
                                    ->helperText('Se o erro na calibração superar este valor, o instrumento será considerado reprovado.'),

                                Grid::make(2)->schema([
                                    TextInput::make('measuring_range')->label('Faixa de Medição (ex: 0-150mm)'),
                                    TextInput::make('resolution')
                                        ->label('Resolução (Menor Divisão)')
                                        ->placeholder('Ex: 0.01 mm')
                                        ->helperText('O menor valor que o instrumento consegue indicar.'),
                                    ]),
                                TextInput::make('location')->label('Localização')->maxLength(255),
                                DatePicker::make('acquisition_date')->label('Data de Aquisição')->required(),
                                DatePicker::make('calibration_due')->label('Venc. da Calibração')->required(),
                                Select::make('status')->label('Status')
                                    ->options(['active' => 'Ativo', 'in_calibration' => 'Em Calibração', 'expired' => 'Vencido'])
                                    ->default('active')->required(),
                                RichEditor::make('notes')->label('')
                                    ->columnSpanFull(),

                            ])->columns(2),
                    ])->columnSpan(3),
                ])->columnSpanFull(),
            ]);
    }
}
