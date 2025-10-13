<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\InstrumentTypeResource;

class CreateInstrumentType extends CreateRecord
{
    protected static string $resource = InstrumentTypeResource::class;
}
