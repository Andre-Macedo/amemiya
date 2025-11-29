<?php

namespace App\Filament\Clusters\System;

use Filament\Clusters\Cluster;

class SystemCluster extends Cluster
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Administração';

    protected static ?string $slug = 'administracao';

    protected static ?int $navigationSort = -10;


    public static function getPages(): array
    {
        return [
            'index' => Pages\Dashboard::route('/'),
        ];
    }



}
