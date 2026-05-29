<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IoTGatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('station_id')
                    ->relationship('station', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('device_id')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('online'),
            ]);
    }
}
