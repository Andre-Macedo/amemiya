<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateChecks\Pages;

use Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIntermediateCheck extends EditRecord
{
    protected static string $resource = IntermediateCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
