<?php

namespace Modules\Metrology\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class MetrologyPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Metrology';
    }

    public function getId(): string
    {
        return 'metrology';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
