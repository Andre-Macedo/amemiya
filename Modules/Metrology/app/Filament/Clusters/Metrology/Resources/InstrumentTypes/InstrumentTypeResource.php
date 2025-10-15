<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Pages\CreateInstrumentType;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Pages\EditInstrumentType;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Pages\ListInstrumentTypes;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Pages\ViewInstrumentType;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Schemas\InstrumentTypeForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Schemas\InstrumentTypeInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\InstrumentTypes\Tables\InstrumentTypesTable;
use Modules\Metrology\Models\InstrumentType;

class InstrumentTypeResource extends Resource
{
    protected static ?string $model = InstrumentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $cluster = MetrologyCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Instrumentos';

    protected static ?string $navigationLabel = 'Tipos de Instrumentos';

    public static function form(Schema $schema): Schema
    {
        return InstrumentTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InstrumentTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstrumentTypesTable::configure($table);
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
            'index' => ListInstrumentTypes::route('/'),
            'create' => CreateInstrumentType::route('/create'),
            'view' => ViewInstrumentType::route('/{record}'),
            'edit' => EditInstrumentType::route('/{record}/edit'),
        ];
    }
}
