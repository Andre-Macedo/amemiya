<?php

namespace App;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NavigationGroup: string implements HasIcon, HasLabel
{
    case Corporate = 'corporate';
    case Security = 'security';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Corporate => 'Corporativo',
            self::Security => 'Segurança e Acessos',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Corporate => 'heroicon-o-building-office',
            self::Security => 'heroicon-o-shield-check',
        };
    }
}
