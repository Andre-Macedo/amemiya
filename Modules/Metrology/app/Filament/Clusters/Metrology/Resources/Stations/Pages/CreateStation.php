<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\StationResource;

class CreateStation extends CreateRecord
{
    protected static string $resource = StationResource::class;
}
