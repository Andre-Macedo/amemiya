<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Schemas;

use Filament\Forms\Components\Select;
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

                Select::make('decision_rule')
                    ->label('Regra de Decisão (ISO 17025)')
                    ->options([
                        'simple' => 'Aceitação Simples (Erro <= MPE)',
                        'uncertainty_accounted' => 'Incerteza Somada (Erro + U <= MPE)',
                        'guard_band' => 'Banda de Guarda (ILAC G8 Binary)',
                    ])
                    ->default('simple')
                    ->required()
                    ->live()
                    ->helperText('Define como o resultado Aprovação/Reprovação será calculado automaticamente.'),

                TextInput::make('guard_band_multiplier')
                    ->label('Multiplicador da Banda de Guarda (w)')
                    ->numeric()
                    ->default(1.0)
                    ->required()
                    ->visible(fn ($get) => $get('decision_rule') === 'guard_band')
                    ->helperText('Geralmente 1.0. Define a largura da zona de aceitação reduzida.'),
            ]);
    }
}
