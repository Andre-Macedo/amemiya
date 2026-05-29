<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\IoT\Filament\Clusters\IoT\IoTCluster;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\Pages\CreateIoTGateway;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\Pages\EditIoTGateway;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\Pages\ListIoTGateways;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\Schemas\IoTGatewayForm;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTGateways\Tables\IoTGatewaysTable;
use Modules\IoT\Models\IoTGateway;

class IoTGatewayResource extends Resource
{
    protected static ?string $model = IoTGateway::class;

    protected static ?string $cluster = IoTCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $navigationLabel = 'IoT Gateways';

    protected static ?string $modelLabel = 'Gateway IoT';

    protected static ?string $pluralModelLabel = 'Gateways IoT';

    public static function form(Schema $schema): Schema
    {
        return IoTGatewayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IoTGatewaysTable::configure($table);
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
            'index' => ListIoTGateways::route('/'),
            'create' => CreateIoTGateway::route('/create'),
            'edit' => EditIoTGateway::route('/{record}/edit'),
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
