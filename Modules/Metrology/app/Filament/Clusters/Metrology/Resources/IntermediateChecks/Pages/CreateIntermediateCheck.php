<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateChecks\Pages;

use Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateCheckResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntermediateCheck extends CreateRecord
{
    protected static string $resource = IntermediateCheckResource::class;
}
