<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InstrumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('calibration_frequency_months')
                    ->label('Frequência Padrão (Meses)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(120)
                    ->default(12)
                    ->required()
                    ->helperText('Define o intervalo padrão para calcular o vencimento de novos instrumentos.'),
            ]);
    }
}
