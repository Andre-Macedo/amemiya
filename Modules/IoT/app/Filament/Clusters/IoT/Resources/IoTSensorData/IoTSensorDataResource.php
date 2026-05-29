<?php

namespace Modules\IoT\Filament\Clusters\IoT\Resources\IoTSensorData;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\IoT\Filament\Clusters\IoT\IoTCluster;
use Modules\IoT\Filament\Clusters\IoT\Resources\IoTSensorData\Pages\ManageIoTSensorData;
use Modules\IoT\Models\IoTSensorData;

class IoTSensorDataResource extends Resource
{
    protected static ?string $model = IoTSensorData::class;

    protected static ?string $cluster = IoTCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'IoT Sensor Data';

    protected static ?string $modelLabel = 'Dado de Sensor IoT';

    protected static ?string $pluralModelLabel = 'Dados de Sensores IoT';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('node_id')
                    ->relationship('node', 'name')
                    ->required(),
                TextInput::make('msg_id')
                    ->numeric(),
                TextInput::make('rpm')
                    ->numeric(),
                TextInput::make('rms_global')
                    ->numeric(),
                TextInput::make('rms_x')
                    ->numeric(),
                TextInput::make('rms_y')
                    ->numeric(),
                TextInput::make('rms_z')
                    ->numeric(),
                TextInput::make('kurt_x')
                    ->numeric(),
                TextInput::make('kurt_y')
                    ->numeric(),
                TextInput::make('kurt_z')
                    ->numeric(),
                TextInput::make('piezo_rms')
                    ->numeric(),
                TextInput::make('piezo_pico_max')
                    ->numeric(),
                TextInput::make('piezo_fator_crista')
                    ->numeric(),
                TextInput::make('fft_data'),
                TextInput::make('ml_status'),
                TextInput::make('ml_confidence')
                    ->numeric(),
                DateTimePicker::make('measured_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('tenant.name')
                    ->searchable(),
                TextColumn::make('node.name')
                    ->searchable(),
                TextColumn::make('msg_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rpm')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rms_global')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rms_x')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rms_y')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rms_z')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('kurt_x')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('kurt_y')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('kurt_z')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('piezo_rms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('piezo_pico_max')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('piezo_fator_crista')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ml_status')
                    ->searchable(),
                TextColumn::make('ml_confidence')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('measured_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIoTSensorData::route('/'),
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
