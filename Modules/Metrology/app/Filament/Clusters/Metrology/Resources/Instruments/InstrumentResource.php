<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages\CreateInstrument;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages\EditInstrument;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages\ListInstruments;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Pages\ViewInstrument;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\RelationManagers\CalibrationRelationManager;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas\InstrumentForm;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Schemas\InstrumentInfolist;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Tables\InstrumentsTable;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Instruments\Widgets\InstrumentStatsWidget;
use Modules\Metrology\Models\Instrument;

/**
 * Recurso Filament para gerenciamento de Instrumentos.
 *
 * Fornece CRUD completo para instrumentos de medição, incluindo histórico de calibrações
 * estatísticas e controle de status.
 */
class InstrumentResource extends Resource
{
    protected static ?string $model = Instrument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $cluster = MetrologyCluster::class;

    protected static ?string $navigationLabel = 'Instrumentos';

    protected static ?string $pluralModelLabel = 'Instrumentos';

    protected static ?string $modelLabel = 'Instrumento';

    /**
     * Define o formulário de instrumentos.
     */
    public static function form(Schema $schema): Schema
    {
        return InstrumentForm::configure($schema);
    }

    /**
     * Define a visualização (InfoList) de detalhes do instrumento.
     */
    public static function infolist(Schema $schema): Schema
    {
        return InstrumentInfolist::configure($schema);
    }

    /**
     * Define a tabela de listagem de instrumentos.
     */
    public static function table(Table $table): Table
    {
        return InstrumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CalibrationRelationManager::class,

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

    public static function getWidgets(): array
    {
        return [
            InstrumentStatsWidget::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Instrument::where('status', ItemStatus::Rejected)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
