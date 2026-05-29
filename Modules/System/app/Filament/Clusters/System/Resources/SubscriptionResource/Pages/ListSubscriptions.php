<?php

namespace Modules\System\Filament\Clusters\System\Resources\SubscriptionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\System\Filament\Clusters\System\Resources\SubscriptionResource;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
