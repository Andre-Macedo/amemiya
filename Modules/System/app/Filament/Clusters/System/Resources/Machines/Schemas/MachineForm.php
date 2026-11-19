<?php

namespace Modules\System\Filament\Clusters\System\Resources\Machines\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MachineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação da Máquina')
                    ->columns(2)
                    ->schema([
                        Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->required(),
                        Select::make('station_id')
                            ->relationship('station', 'name'),
                        TextInput::make('name')
                            ->label('Nome')
                            ->required(),
                        TextInput::make('code')
                            ->label('Código'),
                        TextInput::make('status')
                            ->required()
                            ->default('operational'),
                        Textarea::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ]),

                Section::make('Parâmetros Dinâmicos & Rolamento (ISO 20816 / Cinemática)')
                    ->description('Define o modelo do rolamento e o grupo normativo usados como indicação espectral no Spectrum Studio.')
                    ->columns(3)
                    ->schema([
                        Select::make('metadata.bearing_model')
                            ->label('Rolamento do Mancal')
                            ->options([
                                'skf_6203' => 'SKF 6203 (8 esferas - Motor 1/4 CV)',
                                'skf_6204' => 'SKF 6204 (8 esferas)',
                                'ucp_204' => 'UCP 204 - Mancal Pedestal',
                                'skf_6205' => 'SKF 6205 (9 esferas)',
                                'skf_6304' => 'SKF 6304 (7 esferas)',
                                'skf_2206' => 'SKF 2206 (Autocompensador)',
                                'custom' => 'Customizado (Informar geometria)',
                            ])
                            ->default('skf_6204')
                            ->live(),
                        Select::make('metadata.iso_group')
                            ->label('Classe ISO 20816 / 10816')
                            ->options([
                                'baseline_only' => 'Baseline / IA apenas (< 15 kW — Sem Zonas ISO)',
                                'small_machine' => 'Referência ISO 10816-1 Classe I (< 15 kW)',
                                'group_2' => 'ISO 20816-3 Grupo 2 (15 kW a 300 kW)',
                                'group_1' => 'ISO 20816-3 Grupo 1 (> 300 kW)',
                            ])
                            ->default('baseline_only'),
                        Select::make('metadata.foundation_type')
                            ->label('Base Mecânica')
                            ->options([
                                'rigid' => 'Rígida',
                                'flexible' => 'Flexível',
                            ])
                            ->default('rigid'),
                        TextInput::make('metadata.nominal_rpm')
                            ->label('RPM Nominal (Fallback)')
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(30000),
                        TextInput::make('metadata.bearing_geometry.bearing_name')
                            ->label('Nome do Rolamento Customizado')
                            ->visible(fn (Get $get): bool => $get('metadata.bearing_model') === 'custom'),
                        TextInput::make('metadata.bearing_geometry.n')
                            ->label('Nº Corpos Rolantes (N)')
                            ->numeric()
                            ->integer()
                            ->minValue(3)
                            ->visible(fn (Get $get): bool => $get('metadata.bearing_model') === 'custom')
                            ->required(fn (Get $get): bool => $get('metadata.bearing_model') === 'custom'),
                        TextInput::make('metadata.bearing_geometry.d')
                            ->label('Diâmetro da Esfera d (mm)')
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0.5)
                            ->visible(fn (Get $get): bool => $get('metadata.bearing_model') === 'custom')
                            ->required(fn (Get $get): bool => $get('metadata.bearing_model') === 'custom'),
                        TextInput::make('metadata.bearing_geometry.D')
                            ->label('Diâmetro Primitivo D (mm)')
                            ->numeric()
                            ->step('0.01')
                            ->minValue(1.0)
                            ->visible(fn (Get $get): bool => $get('metadata.bearing_model') === 'custom')
                            ->required(fn (Get $get): bool => $get('metadata.bearing_model') === 'custom'),
                        TextInput::make('metadata.bearing_geometry.alpha')
                            ->label('Ângulo de Contato α (°)')
                            ->numeric()
                            ->step('0.1')
                            ->default(0.0)
                            ->visible(fn (Get $get): bool => $get('metadata.bearing_model') === 'custom'),
                    ]),
            ]);
    }
}
