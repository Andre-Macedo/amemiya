<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Checklists\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ChecklistInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('calibration.id')->label('Calibration ID'),
                TextEntry::make('steps')->formatStateUsing(fn($state) => implode(', ', array_column($state, 'step'))),
                TextEntry::make('completed')->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),
            ])
            ->columns(2);
    }
}
