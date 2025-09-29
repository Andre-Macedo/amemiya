<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\ChecklistResource;

class ListChecklists extends ListRecords
{
    protected static string $resource = ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
