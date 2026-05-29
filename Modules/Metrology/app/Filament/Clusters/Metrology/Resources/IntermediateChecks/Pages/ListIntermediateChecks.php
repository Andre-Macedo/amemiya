<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateChecks\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateCheckResource;

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
