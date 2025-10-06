<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\ChecklistTemplateResource;

class CreateChecklistTemplate extends CreateRecord
{
    protected static string $resource = ChecklistTemplateResource::class;
}
