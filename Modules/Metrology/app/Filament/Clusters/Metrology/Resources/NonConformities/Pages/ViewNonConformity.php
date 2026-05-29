<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformities\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\NonConformityResource;

class ViewNonConformity extends ViewRecord
{
    protected static string $resource = NonConformityResource::class;
}
