<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\ReferenceStandardResource;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Models\ReferenceStandardType;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $relatedResource = ReferenceStandardResource::class;

    protected static ?string $title = 'Itens do Kit / Peças';

    protected static string|null|\BackedEnum $icon = 'heroicon-o-squares-2x2';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->is_kit;
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Nome da Peça')
                ->placeholder('Ex: Bloco 10mm')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Select::make('reference_standard_type_id')
                ->label('Tipo')
                ->options(ReferenceStandardType::pluck('name', 'id'))
                ->required()
                ->searchable(),

            Grid::make(3)->schema([
                TextInput::make('nominal_value')
                    ->label('Valor Nominal')
                    ->numeric()
                    ->required()
                    ->suffix(fn($record) => $record?->unit ?? 'mm'),

                TextInput::make('actual_value')
                    ->label('Valor Real')
                    ->helperText('Preencha se já tiver certificado')
                    ->numeric(),

                TextInput::make('uncertainty')
                    ->label('Incerteza')
                    ->numeric(),
            ]),

            Hidden::make('status')->default('active'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nome da Peça')
                    ->required()
                    ->maxLength(255),

                Select::make('reference_standard_type_id')
                    ->label('Tipo')
                    ->options(ReferenceStandardType::pluck('name', 'id'))
                    ->required(),

                Grid::make(3)->schema([
                    TextInput::make('nominal_value')
                        ->label('Valor Nominal')
                        ->numeric()
                        ->required(),

                    TextInput::make('actual_value')
                        ->label('Valor Real')
                        ->numeric(),

                    TextInput::make('uncertainty')
                        ->label('Incerteza')
                        ->numeric(),
                ]),

                Hidden::make('status')->default('active'),
            ]);
    }

    public function table(Table $table): Table
    {

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Peça')

                    ->searchable(),

                TextColumn::make('nominal_value')
                    ->label('Nominal')
                    ->suffix(fn($record) => ' ' . $record->unit)
                    ->sortable(),

                TextColumn::make('actual_value')
                    ->label('Valor Real')
                    ->placeholder('-'),

                TextColumn::make('uncertainty')
                    ->label('Incerteza')
                    ->placeholder('-'),
            ])
            ->headerActions([
                Action::make('create_child')
                    ->label('Adicionar Peça')
                    ->icon('heroicon-o-plus')
                    ->slideOver() // Abre na lateral
                    ->modalWidth('md')
                    ->form($this->getFormSchema()) // Usa nosso schema
                    ->action(function (array $data, \Livewire\Component $livewire) {
                        // 1. Pega o Pai (O Kit que estamos visualizando)
                        $parentKit = $livewire->getOwnerRecord();
                        // 2. Cria o Filho vinculado manualmente
                        $child = ReferenceStandard::create([
                            'parent_id' => $parentKit->id, // Vínculo Crucial
                            'name' => $data['name'],
                            'reference_standard_type_id' => $data['reference_standard_type_id'],
                            'nominal_value' => $data['nominal_value'],
                            'actual_value' => $data['actual_value'] ?? null,
                            'uncertainty' => $data['uncertainty'] ?? null,
                            'status' => 'active', // Default
                            // Opcional: Herdar dados do pai se quiser
                            'calibration_due' => $parentKit->calibration_due,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Peça adicionada ao Kit')
                            ->send();
                    }),
                ])
            ->actions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
