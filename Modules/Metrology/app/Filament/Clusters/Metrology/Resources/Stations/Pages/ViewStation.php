<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\StationResource;

class ViewStation extends ViewRecord
{
    protected static string $resource = StationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
