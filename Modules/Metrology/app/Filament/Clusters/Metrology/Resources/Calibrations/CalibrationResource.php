<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages\CreateCalibration;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages\EditCalibration;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages\ListCalibrations;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages\ViewCalibration;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas\CalibrationForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas\CalibrationInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Tables\CalibrationsTable;
use Modules\Metrology\Models\Calibration;

class CalibrationResource extends Resource
{
    protected static ?string $model = Calibration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $cluster = MetrologyCluster::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Calibrações';

    protected static ?string $navigationLabel = 'Calibrações';

    protected static ?string $modelLabel = 'Calibração';
    protected static ?string $pluralModelLabel = 'Calibrações';


    public static function form(Schema $schema): Schema
    {
        return CalibrationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CalibrationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CalibrationsTable::configure($table);
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
            'index' => ListCalibrations::route('/'),
            'create' => CreateCalibration::route('/create'),
            'view' => ViewCalibration::route('/{record}'),
            'edit' => EditCalibration::route('/{record}/edit'),
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
