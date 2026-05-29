<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Modules\System\Filament\Clusters\System\SystemCluster;

class Logs extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Logs do Sistema';

    protected static ?string $title = 'Logs do Servidor';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.logs';

    public static function shouldRegisterNavigation(): bool
    {
        // Apenas Super Admins podem ver os logs
        return auth()->user()?->hasRole('super-admin') ?? false;
    }
}
