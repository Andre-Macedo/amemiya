<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages\CreateInstrument;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages\EditInstrument;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages\ListInstruments;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages\ViewInstrument;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas\InstrumentForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas\InstrumentInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Tables\InstrumentsTable;
use Modules\Metrology\Models\Instrument;

class InstrumentResource extends Resource
{
    protected static ?string $model = Instrument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $cluster = MetrologyCluster::class;

    public static function form(Schema $schema): Schema
    {
        return InstrumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InstrumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstrumentsTable::configure($table);
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
            'index' => ListInstruments::route('/'),
            'create' => CreateInstrument::route('/create'),
            'view' => ViewInstrument::route('/{record}'),
            'edit' => EditInstrument::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
