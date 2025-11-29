<?php

namespace App\Filament\Clusters\System\Resources\Stations\Pages;

use App\Filament\Clusters\System\Resources\Stations\StationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStations extends ListRecords
{
    protected static string $resource = StationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modal(),
        ];
    }
}
