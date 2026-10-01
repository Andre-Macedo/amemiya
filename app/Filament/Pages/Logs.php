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
        // Descontinuado da navegação em favor dos logs estruturados de auditoria e IoT.
        // Permanece acessível apenas diretamente via /log-viewer para sysadmins.
        return false;
    }
}
