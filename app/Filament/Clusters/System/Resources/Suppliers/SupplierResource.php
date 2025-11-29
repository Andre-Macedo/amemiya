<?php

namespace App\Filament\Clusters\System\Resources\Suppliers;

use App\Filament\Clusters\System\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Clusters\System\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Clusters\System\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Clusters\System\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Clusters\System\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Clusters\System\Resources\Suppliers\Schemas\SupplierInfolist;
use App\Filament\Clusters\System\Resources\Suppliers\Tables\SuppliersTable;
use App\Filament\Clusters\System\SystemCluster;
use App\Models\Supplier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?string $modelLabel = 'Fornecedor / Parceiro';
    protected static ?string $pluralModelLabel = 'Fornecedores / Parceiros';

    protected static string|null|\UnitEnum $navigationGroup = 'Corporativo';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
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
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
