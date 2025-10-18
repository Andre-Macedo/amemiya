<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\Pages\ListStations;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\Schemas\StationForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\Schemas\StationInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Stations\Tables\StationsTable;
use Modules\Metrology\Models\Station;

class StationResource extends Resource
{
    protected static ?string $model = Station::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $cluster = MetrologyCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Operações';

    protected static ?string $navigationLabel = 'Estações de Trabalho';


    public static function form(Schema $schema): Schema
    {
        return StationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StationsTable::configure($table);
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
            'index' => ListStations::route('/'),
//            'create' => CreateStation::route('/create'),
//            'view' => ViewStation::route('/{record}'),
//            'edit' => EditStation::route('/{record}/edit'),
        ];
    }
}
