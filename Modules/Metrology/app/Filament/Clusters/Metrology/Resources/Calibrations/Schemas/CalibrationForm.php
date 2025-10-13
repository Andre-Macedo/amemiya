<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;

class CalibrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Calibration Details')
                        ->schema([
                            Section::make()->schema([
                                Select::make('instrument_id')
                                    ->relationship('instrument', 'name')
                                    ->live()
                                    ->searchable()
                                    ->required()
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
//                                        if (!$state) {
//                                            return;
//                                        }
                                        $instrument = Instrument::find($state);
                                        $template = ChecklistTemplate::where('instrument_type_id', $instrument->instrument_type_id)->first();
                                        if ($template) {
                                            $items = $template->items->map(fn ($item) => [
                                                'step' => $item->step,
                                                'question_type' => $item->question_type,
                                                'required_readings' => $item->required_readings,
                                            ])->toArray();
                                            $set('checklist_items', $items);
                                        }
                                    }),
                                DatePicker::make('calibration_date')
                                    ->required()
                                    ->default(now()),
                                Select::make('type')
                                    ->options([
                                        'internal' => 'Interna',
                                        'external_rbc' => 'Externa RBC',
                                    ])
                                    ->default('internal')
                                    ->live()
                                    ->required(),
                                Select::make('performed_by_id')
                                    ->relationship('performedBy', 'name')
                                    ->searchable()
                                    ->required(),
                            ])->columns(2),
                        ]),
                    Step::make('Checklist')
                        ->schema([
                            Repeater::make('checklist_items')
                                ->schema([
                                    TextInput::make('step')->disabled(),
                                    Select::make('question_type')
                                        ->options([
                                            'boolean' => 'Boolean',
                                            'numeric' => 'Numeric',
                                            'text' => 'Text',
                                        ])->disabled(),
                                    Toggle::make('completed')->visible(fn (Get $get) => $get('question_type') === 'boolean'),
                                    Repeater::make('readings')
                                        ->schema([
                                            TextInput::make('value')->numeric()->required(),
                                        ])
                                        ->minItems(fn (Get $get) => $get('required_readings') ?: 1)
                                        ->maxItems(fn (Get $get) => $get('required_readings') ?: 1)
                                        ->visible(fn (Get $get) => $get('question_type') === 'numeric'),
                                    Textarea::make('notes')->visible(fn (Get $get) => $get('question_type') === 'text'),
                                ])
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->columnSpanFull(),
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'internal' && !empty($get('instrument_id'))),
                    Step::make('Results and Notes')
                        ->schema([
                            Section::make()->schema([
                                Select::make('result')
                                    ->options([
                                        'approved' => 'Aprovado',
                                        'rejected' => 'Rejeitado',
                                    ]),
                                TextInput::make('deviation')->numeric()->suffix('mm'),
                                TextInput::make('uncertainty')->numeric()->suffix('mm'),
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
                                Textarea::make('notes')->columnSpanFull(),
                            ])->columns(2),
                        ]),
                    Step::make('Certificate')
                        ->schema([
                            FileUpload::make('certificate_path')
                                ->directory('calibration-certificates')
                                ->acceptedFileTypes(['application/pdf'])
                                ->required(),
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'external_rbc'),
                ])->columnSpanFull(),
            ]);
    }
}
