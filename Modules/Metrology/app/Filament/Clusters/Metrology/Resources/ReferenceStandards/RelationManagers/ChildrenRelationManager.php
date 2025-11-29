<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\ReferenceStandardResource;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $relatedResource = ReferenceStandardResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
