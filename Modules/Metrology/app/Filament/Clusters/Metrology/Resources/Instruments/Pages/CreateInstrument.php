<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages;

use App\Filament\Concerns\InteractsWithCluster;
use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\InstrumentResource;

class CreateInstrument extends CreateRecord
{
    use InteractsWithCluster;

    protected static string $resource = InstrumentResource::class;

}
