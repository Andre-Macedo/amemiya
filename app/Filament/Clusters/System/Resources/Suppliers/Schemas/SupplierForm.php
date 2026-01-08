<?php

namespace App\Filament\Clusters\System\Resources\Suppliers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados Cadastrais')
                    ->schema([
                        TextInput::make('name')
                            ->label('Razão Social')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('trade_name')
                            ->label('Nome Fantasia'),
                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->mask('99.999.999/9999-99')
                            ->unique(ignoreRecord: true),
                        TextInput::make('email')
                            ->email(),
                        TextInput::make('phone')
                            ->tel(),
                    ])->columns(2),

                Section::make('Capacidades e Qualificação')
                    ->description('Defina o papel deste parceiro no sistema.')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('is_manufacturer')
                                ->label('Fabricante')
                                ->helperText('Vende instrumentos novos.'),
                            Toggle::make('is_calibration_provider')
                                ->label('Laboratório de Calibração')
                                ->helperText('Presta serviços de calibração.')
                                ->live(),
                            Toggle::make('is_maintenance_provider')
                                ->label('Prestador de Manutenção'),
                        ]),

                        Group::make()->schema([
                            TextInput::make('rbc_code')
                                ->label('Código RBC/CRL')
                                ->prefix('CRL'),
                            DatePicker::make('accreditation_valid_until')
                                ->label('Validade da Acreditação (ISO 17025)')
                                ->native(false),
                            
                            \Filament\Forms\Components\FileUpload::make('accreditation_certificate')
                                ->label('Certificado de Acreditação (PDF)')
                                ->directory('supplier-certificates')
                                ->acceptedFileTypes(['application/pdf'])
                                ->openable()
                                ->downloadable()
                                ->columnSpanFull(),
                        ])
                            ->columns(2)
                            ->visible(fn (Get $get) => $get('is_calibration_provider')),
                    ]),
            ]);
    }
}
