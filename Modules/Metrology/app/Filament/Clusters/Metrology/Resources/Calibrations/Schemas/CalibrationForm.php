<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;

class CalibrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Calibration')
                    ->tabs([
                        Tabs\Tab::make('Details')
                            ->schema([
                                Select::make('instrument_id')
                                    ->relationship('instrument', 'name')
                                    ->required()
                                    ->searchable(),
                                Select::make('reference_standards')
                                    ->relationship('referenceStandards', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->required(),
                                DatePicker::make('calibration_date')
                                    ->required(),
                                Select::make('type')
                                    ->options([
                                        'internal' => 'Interna',
                                        'external_rbc' => 'Externa RBC',
                                    ])
                                    ->default('internal')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('certificate_path', $state === 'external_rbc' ? '' : null);
                                    }),
                                Select::make('result')
                                    ->options([
                                        'approved' => 'Aprovado',
                                        'rejected' => 'Rejeitado',
                                    ])
                                    ->nullable(),
                                TextInput::make('deviation')
                                    ->numeric()
                                    ->suffix('mm')
                                    ->nullable(),
                                TextInput::make('uncertainty')
                                    ->numeric()
                                    ->suffix('mm')
                                    ->nullable(),
                                Select::make('performed_by_id')
                                    ->relationship('performedBy', 'name')
                                    ->required()
                                    ->searchable(),
                                Select::make('calibration_interval')
                                    ->label('Intervalo até Próxima Calibração (meses)')
                                    ->options([
                                        6 => '6 meses',
                                        12 => '12 meses',
                                        18 => '18 meses',
                                        24 => '24 meses',
                                    ])
                                    ->default(12)
                                    ->required(),
                                Textarea::make('notes')
                                    ->nullable()
                                    ->columnSpanFull(),
                                FileUpload::make('certificate_path')
                                    ->directory('calibration-certificates')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->required(fn ($get) => $get('type') === 'external_rbc')
                                    ->nullable(fn ($get) => $get('type') !== 'external_rbc'),
                            ])
                            ->columns(2),
                        Tabs\Tab::make('Checklist')
                            ->schema([
                                Select::make('checklist_template_id')
                                    ->relationship('checklist.checklistTemplate', 'name')
                                    ->required(fn ($record) => !$record?->checklist_id)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $record) {
                                        if ($state && !$record?->checklist_id) {
                                            $template = \Modules\Metrology\Models\ChecklistTemplate::with('items')->find($state);
                                            $items = $template->items->map(fn ($item) => [
                                                'step' => $item->step,
                                                'question_type' => $item->question_type,
                                                'order' => $item->order,
                                                'required_readings' => $item->required_readings,
                                                'reference_standard_type' => $item->reference_standard_type,
                                            ])->toArray();
                                            $set('checklist_items', $items);
                                        }
                                    }),
                                Repeater::make('checklist_items')
                                    ->schema([
                                        TextInput::make('step')->required()->disabled(),
                                        Hidden::make('question_type'),
                                        Hidden::make('order'),
                                        Hidden::make('required_readings'),
                                        TextInput::make('reference_standard_type')->disabled(),
                                        Toggle::make('completed')
                                            ->visible(fn ($get) => $get('question_type') === 'boolean')
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, $get, callable $set) {
                                                if ($state && $get('question_type') === 'boolean') {
                                                    $set('result', $state ? 'pass' : 'fail');
                                                }
                                            }),
                                        Repeater::make('readings')
                                            ->label('Measurements')
                                            ->schema([
                                                TextInput::make('value')
                                                    ->numeric()
                                                    ->required()
                                                    ->reactive(),
                                            ])
                                            ->visible(fn ($get) => $get('question_type') === 'numeric')
                                            ->itemLabel(fn ($state, $get) => 'Reading ' . ($get('index') + 1))
                                            ->defaultItems(fn ($get) => $get('required_readings') ?? 1)
                                            ->minItems(fn ($get) => $get('required_readings') ?? 1)
                                            ->maxItems(fn ($get) => $get('required_readings') ?? 1)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, $get, callable $set) {
                                                if ($get('question_type') === 'numeric' && !empty($state)) {
                                                    $values = array_column($state, 'value');
                                                    $mean = count($values) > 0 ? array_sum($values) / count($values) : 0;
                                                    $variance = count($values) > 1 ? array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / (count($values) - 1) : 0;
                                                    $uncertainty = count($values) > 1 ? sqrt($variance) / sqrt(count($values)) : 0;
                                                    $set('uncertainty', $uncertainty);
                                                }
                                            }),
                                        Textarea::make('notes')
                                            ->visible(fn ($get) => $get('question_type') === 'text' || $get('question_type') === 'numeric'),
                                        TextInput::make('uncertainty')
                                            ->numeric()
                                            ->disabled()
                                            ->visible(fn ($get) => $get('question_type') === 'numeric'),
                                        TextInput::make('result')
                                            ->disabled()
                                            ->visible(fn ($get) => $get('question_type') !== 'text'),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(0)
                                    ->afterStateUpdated(function ($state, $record, callable $set) {
                                        if ($record?->checklist_id && !empty($state)) {
                                            $checklist = $record->checklist;
                                            $checklist->items()->delete();
                                            $items = array_map(function ($item) use ($checklist) {
                                                return [
                                                    'checklist_id' => $checklist->id,
                                                    'step' => $item['step'],
                                                    'question_type' => $item['question_type'],
                                                    'order' => $item['order'],
                                                    'required_readings' => $item['required_readings'],
                                                    'reference_standard_type' => $item['reference_standard_type'] ?? null,
                                                    'completed' => $item['completed'] ?? false,
                                                    'readings' => $item['readings'] ? array_column($item['readings'], 'value') : null,
                                                    'uncertainty' => $item['uncertainty'] ?? null,
                                                    'result' => $item['result'] ?? null,
                                                    'notes' => $item['notes'] ?? null,
                                                ];
                                            }, $state);
                                            \Modules\Metrology\Models\ChecklistItem::insert($items);
                                            $checklist->update(['completed' => !in_array(false, array_column($state, 'completed'))]);
                                        }
                                    })
                                    ->dehydrated(false),
                            ])
                            ->visible(fn ($record) => $record?->type !== 'external_rbc'),
                    ])
                ->columnSpanFull(),
            ]);
    }
}
