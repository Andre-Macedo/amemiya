<?php

namespace Modules\System\Filament\Clusters\System\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\System\Filament\Clusters\System\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
