<?php

namespace Modules\IoT\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class IoTPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'IoT';
    }

    public function getId(): string
    {
        return 'iot';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
