<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IoTNodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('gateway_id')
                    ->relationship('gateway', 'name')
                    ->required(),
                Select::make('machine_id')
                    ->relationship('machine', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('node_id')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
