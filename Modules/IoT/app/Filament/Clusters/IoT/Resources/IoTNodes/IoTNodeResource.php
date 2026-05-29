<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\IoT\Filament\Clusters\IoT\IoTCluster;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\Pages\CreateIoTNode;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\Pages\EditIoTNode;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\Pages\ListIoTNodes;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\Schemas\IoTNodeForm;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTNodes\Tables\IoTNodesTable;
use Modules\IoT\Models\IoTNode;

class IoTNodeResource extends Resource
{
    protected static ?string $model = IoTNode::class;

    protected static ?string $cluster = IoTCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'IoT Nodes';

    protected static ?string $modelLabel = 'Node IoT';

    protected static ?string $pluralModelLabel = 'Nodes IoT';

    public static function form(Schema $schema): Schema
    {
        return IoTNodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IoTNodesTable::configure($table);
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
            'index' => ListIoTNodes::route('/'),
            'create' => CreateIoTNode::route('/create'),
            'edit' => EditIoTNode::route('/{record}/edit'),
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
