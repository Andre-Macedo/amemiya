<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Pages;

use App\Filament\Concerns\InteractsWithCluster;
use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\ReferenceStandardResource;

class CreateReferenceStandard extends CreateRecord
{
    use InteractsWithCluster;

    protected static string $resource = ReferenceStandardResource::class;
}
