<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Name'),
                TextEntry::make('location')->label('Location'),

            ])->columns(2);
    }
}
