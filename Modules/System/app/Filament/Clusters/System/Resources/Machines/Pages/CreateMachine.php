<?php

namespace Modules\System\Filament\Clusters\System\Resources\Machines\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\System\Filament\Clusters\System\Resources\Machines\MachineResource;

class CreateMachine extends CreateRecord
{
    protected static string $resource = MachineResource::class;
}
