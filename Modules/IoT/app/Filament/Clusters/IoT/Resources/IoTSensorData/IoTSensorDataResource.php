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
                TextInput::make('mic_rms')
                    ->numeric(),
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
                TextColumn::make('measured_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('node.name')
                    ->searchable(),
                TextColumn::make('rms_global')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('mic_rms')
                    ->label('Mic RMS')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ml_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'saudavel', 'normal' => 'success',
                        'desbalanceamento', 'falha' => 'danger',
                        'desligada' => 'gray',
                        default => 'warning',
                    })
                    ->searchable(),
                TextColumn::make('ml_confidence')
                    ->label('Confiança')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIoTSensorData::route('/'),
        ];
    }
}
