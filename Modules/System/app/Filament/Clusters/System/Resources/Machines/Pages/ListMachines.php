<?php

namespace Modules\System\Filament\Clusters\System\Resources\Machines\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\System\Filament\Clusters\System\Resources\Machines\MachineResource;

class ListMachines extends ListRecords
{
    protected static string $resource = MachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
