<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\InstrumentResource;

class EditInstrument extends EditRecord
{
    protected static string $resource = InstrumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
