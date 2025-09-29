<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\StationResource;

class EditStation extends EditRecord
{
    protected static string $resource = StationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
