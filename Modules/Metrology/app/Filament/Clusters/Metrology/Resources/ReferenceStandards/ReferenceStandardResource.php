<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Pages\CreateReferenceStandard;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Pages\EditReferenceStandard;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Pages\ListReferenceStandards;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Pages\ViewReferenceStandard;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Schemas\ReferenceStandardForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Schemas\ReferenceStandardInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Tables\ReferenceStandardsTable;
use Modules\Metrology\Models\ReferenceStandard;

class ReferenceStandardResource extends Resource
{
    protected static ?string $model = ReferenceStandard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MetrologyCluster::class;

    public static function form(Schema $schema): Schema
    {
        return ReferenceStandardForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReferenceStandardInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReferenceStandardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferenceStandards::route('/'),
            'create' => CreateReferenceStandard::route('/create'),
            'view' => ViewReferenceStandard::route('/{record}'),
            'edit' => EditReferenceStandard::route('/{record}/edit'),
        ];
    }
}
