<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateChecks\Pages;

use Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIntermediateChecks extends ListRecords
{
    protected static string $resource = IntermediateCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
