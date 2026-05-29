<?php

namespace Modules\System\Filament\Clusters\System\Resources\Stations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\System\Filament\Clusters\System\Resources\Stations\StationResource;

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
