<?php

namespace Modules\System\Filament\Clusters\System\Resources\Machines;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\System\Filament\Clusters\System\Resources\Machines\Pages\CreateMachine;
use Modules\System\Filament\Clusters\System\Resources\Machines\Pages\EditMachine;
use Modules\System\Filament\Clusters\System\Resources\Machines\Pages\ListMachines;
use Modules\System\Filament\Clusters\System\Resources\Machines\Schemas\MachineForm;
use Modules\System\Filament\Clusters\System\Resources\Machines\Tables\MachinesTable;
use Modules\System\Filament\Clusters\System\SystemCluster;
use Modules\System\Models\Machine;

class MachineResource extends Resource
{
    protected static ?string $model = Machine::class;

    protected static ?string $cluster = SystemCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MachineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MachinesTable::configure($table);
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
            'index' => ListMachines::route('/'),
            'create' => CreateMachine::route('/create'),
            'edit' => EditMachine::route('/{record}/edit'),
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
