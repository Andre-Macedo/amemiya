<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\IoTNodeResource;

class ListIoTNodes extends ListRecords
{
    protected static string $resource = IoTNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
