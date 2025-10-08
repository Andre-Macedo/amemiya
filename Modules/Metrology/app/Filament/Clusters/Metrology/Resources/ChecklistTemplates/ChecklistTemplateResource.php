<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Pages\CreateChecklistTemplate;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Pages\EditChecklistTemplate;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Pages\ListChecklistTemplates;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Pages\ViewChecklistTemplate;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Schemas\ChecklistTemplateForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Schemas\ChecklistTemplateInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ChecklistTemplates\Tables\ChecklistTemplatesTable;
use Modules\Metrology\Models\ChecklistTemplate;

class ChecklistTemplateResource extends Resource
{
    protected static ?string $model = ChecklistTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MetrologyCluster::class;

    protected static ?string $navigationLabel = 'Checklists';
    protected static string|null|\UnitEnum $navigationGroup = 'Calibrações';

    public static function form(Schema $schema): Schema
    {
        return ChecklistTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChecklistTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecklistTemplatesTable::configure($table);
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
            'index' => ListChecklistTemplates::route('/'),
            'create' => CreateChecklistTemplate::route('/create'),
            'view' => ViewChecklistTemplate::route('/{record}'),
            'edit' => EditChecklistTemplate::route('/{record}/edit'),
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
