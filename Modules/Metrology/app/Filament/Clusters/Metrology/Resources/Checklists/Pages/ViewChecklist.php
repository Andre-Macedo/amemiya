<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\ChecklistResource;

class ViewChecklist extends ViewRecord
{
    protected static string $resource = ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
