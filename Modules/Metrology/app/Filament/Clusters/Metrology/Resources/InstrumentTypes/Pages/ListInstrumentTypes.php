<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\InstrumentTypeResource;

class ListInstrumentTypes extends ListRecords
{
    protected static string $resource = InstrumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modal(),
        ];
    }
}
