<?php

namespace App\Filament\Clusters\System\Resources\Stations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('location')->maxLength(255),
                Select::make('type')
                    ->label('Tipo de Estação')
                    ->options([
                        'general' => 'Área Comum / Produção',
                        'internal_lab' => 'Laboratório Interno',
                        'external_provider' => 'Fornecedor Externo (Virtual)',
                        'storage' => 'Almoxarifado / Armazenamento',
                    ])
                    ->required()
                    ->default('general'),
            ]);
    }
}
