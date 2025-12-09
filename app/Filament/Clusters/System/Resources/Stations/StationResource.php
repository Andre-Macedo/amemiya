<?php

namespace App\Filament\Clusters\System\Resources\Stations;

use App\Filament\Clusters\System\Resources\Stations\Pages\ListStations;
use App\Filament\Clusters\System\Resources\Stations\Schemas\StationForm;
use App\Filament\Clusters\System\Resources\Stations\Schemas\StationInfolist;
use App\Filament\Clusters\System\Resources\Stations\Tables\StationsTable;
use App\Filament\Clusters\System\SystemCluster;
use App\Models\Station;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;

class StationResource extends Resource
{
    protected static ?string $model = Station::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $cluster = SystemCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Corporativo';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Estações de Trabalho';

    protected static ?string $pluralModelLabel = 'Estações de Trabalho';
    protected static ?string $modelLabel = 'Estação de Trabalho';

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
