<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Pages\CreateReferenceStandardType;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Pages\EditReferenceStandardType;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Pages\ListReferenceStandardTypes;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Pages\ViewReferenceStandardType;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Schemas\ReferenceStandardTypeForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Schemas\ReferenceStandardTypeInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandardTypes\Tables\ReferenceStandardTypesTable;
use Modules\Metrology\Models\ReferenceStandardType;

class ReferenceStandardTypeResource extends Resource
{
    protected static ?string $model = ReferenceStandardType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $cluster = MetrologyCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = "Blocos de Refêrencia";

    public static function form(Schema $schema): Schema
    {
        return ReferenceStandardTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReferenceStandardTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReferenceStandardTypesTable::configure($table);
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
            'index' => ListReferenceStandardTypes::route('/'),
            'create' => CreateReferenceStandardType::route('/create'),
            'view' => ViewReferenceStandardType::route('/{record}'),
            'edit' => EditReferenceStandardType::route('/{record}/edit'),
        ];
    }
}
