<?php

namespace Modules\System\Filament\Clusters\System\Resources\SubscriptionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\System\Filament\Clusters\System\Resources\SubscriptionResource;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
