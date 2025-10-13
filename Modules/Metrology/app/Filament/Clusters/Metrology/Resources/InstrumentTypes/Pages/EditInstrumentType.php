<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\InstrumentTypeResource;

class EditInstrumentType extends EditRecord
{
    protected static string $resource = InstrumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
