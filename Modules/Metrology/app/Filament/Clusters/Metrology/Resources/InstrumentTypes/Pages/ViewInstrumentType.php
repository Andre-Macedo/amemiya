<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\InstrumentTypeResource;

class ViewInstrumentType extends ViewRecord
{
    protected static string $resource = InstrumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
