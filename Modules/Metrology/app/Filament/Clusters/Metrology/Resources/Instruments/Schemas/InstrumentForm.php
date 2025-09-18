<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InstrumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('serial_number')
                    ->required()
                    ->unique()
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        'paquimetro' => 'Paquímetro',
                        'micrometro' => 'Micrômetro',
                        'multimetro' => 'Multímetro',
                    ])
                    ->required(),
                Select::make('precision')
                    ->options([
                        'centesimal' => 'Centesimal',
                        'milesimal' => 'Milesimal',
                    ])
                    ->required(),
                TextInput::make('location')
                    ->maxLength(255),
                DatePicker::make('acquisition_date')
                    ->required(),
                DatePicker::make('calibration_due')
                    ->required(),
                Select::make('status')
                    ->options([
                        'active' => 'Ativo',
                        'in_calibration' => 'Em Calibração',
                        'expired' => 'Vencido',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }
}
