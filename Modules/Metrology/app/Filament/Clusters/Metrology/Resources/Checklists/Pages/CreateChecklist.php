<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\ChecklistResource;

class CreateChecklist extends CreateRecord
{
    protected static string $resource = ChecklistResource::class;
}
