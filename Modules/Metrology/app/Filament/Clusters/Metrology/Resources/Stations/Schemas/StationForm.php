<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\Schemas;

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
            ]);
    }
}
