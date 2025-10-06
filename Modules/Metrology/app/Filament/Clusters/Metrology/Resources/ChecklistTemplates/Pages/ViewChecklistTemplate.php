<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\ChecklistTemplateResource;

class ViewChecklistTemplate extends ViewRecord
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
