<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReferenceStandardTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('calibration_frequency_months')
                    ->label('Frequência Padrão (Meses)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(120)
                    ->default(24)
                    ->required()
                    ->helperText('Define o intervalo para calcular o vencimento de novos padrões deste tipo.'),

            ]);
    }
}
