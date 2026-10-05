<?php

declare(strict_types=1);

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Metrology\Models\InstrumentType;
use Modules\System\Models\Machine;
use Modules\System\Models\Station;
use Modules\System\Models\User;

class SystemDatabaseSeeder extends Seeder
{
    /**
     * Run the system module seeds.
     */
    public function run(): void
    {
        $tenantId = tenant('id');

        // 1. Vincular ou criar Estações de referência se não existirem
        $linhaProducao = Station::firstOrCreate(
            ['name' => 'Linha de Montagem 01'],
            [
                'tenant_id' => $tenantId,
                'type' => 'general',
                'location' => 'Chão de Fábrica - Galpão Principal',
                'description' => 'Linha de usinagem e montagem seriada de componentes de precisão',
                'is_active' => true,
            ]
        );

        $labInternal = Station::firstOrCreate(
            ['name' => 'Laboratório Central'],
            [
                'tenant_id' => $tenantId,
                'type' => 'internal_lab',
                'location' => 'Prédio ADM - Térreo Sala 102',
                'description' => 'Ambiente climatizado e controlado para calibração ISO/IEC 17025',
                'is_active' => true,
            ]
        );

        Station::firstOrCreate(
            ['name' => 'Almoxarifado Geral'],
            [
                'tenant_id' => $tenantId,
                'type' => 'storage',
                'location' => 'Galpão 1 - Setor Logística',
                'description' => 'Guarda e quarentena de instrumentos de medição',
                'is_active' => true,
            ]
        );

        // 2. Máquinas Industriais (Chão de Fábrica e Laboratório)
        $machines = [
            [
                'name' => 'Torno CNC Haas ST-20',
                'code' => 'CNC-01',
                'station_id' => $linhaProducao->id,
                'description' => 'Torno CNC de alta precisão de 2 eixos para torneamento de eixos',
                'status' => 'operational',
                'metadata' => [
                    'brand' => 'Haas',
                    'model' => 'ST-20',
                    'power_kw' => 15,
                    'nominal_rpm' => 4000,
                    'criticality' => 'high',
                ],
            ],
            [
                'name' => 'Injetora Romi 220T',
                'code' => 'INJ-01',
                'station_id' => $linhaProducao->id,
                'description' => 'Injetora hidráulica/elétrica para injeção plástica de precisão',
                'status' => 'operational',
                'metadata' => [
                    'brand' => 'Romi',
                    'model' => 'EN-220',
                    'clamping_force_ton' => 220,
                    'criticality' => 'critical',
                ],
            ],
            [
                'name' => 'Fresadora Universal Clever',
                'code' => 'FRS-01',
                'station_id' => $linhaProducao->id,
                'description' => 'Fresadora ferramenteira com leitor digital para usinagem pesada',
                'status' => 'operational',
                'metadata' => [
                    'brand' => 'Clever',
                    'axes' => 3,
                    'criticality' => 'medium',
                ],
            ],
            [
                'name' => 'Bancada de Controle Dimensional',
                'code' => 'BCD-01',
                'station_id' => $linhaProducao->id,
                'description' => 'Bancada com desempeno de granito, probe tower e periféricos Bluetooth',
                'status' => 'operational',
                'metadata' => [
                    'granite_grade' => '00 (DIN 876)',
                    'dimensions' => '1000x630mm',
                ],
            ],
            [
                'name' => 'Câmara Climatizada Lab',
                'code' => 'CAM-01',
                'station_id' => $labInternal->id,
                'description' => 'Sistema de controle térmico e umidade para ensaios metrológicos',
                'status' => 'operational',
                'metadata' => [
                    'target_temp_c' => 20.0,
                    'target_humidity_pct' => 50.0,
                    'temp_tolerance_c' => 0.5,
                ],
            ],
        ];

        foreach ($machines as $mach) {
            Machine::updateOrCreate(
                ['code' => $mach['code'], 'tenant_id' => $tenantId],
                array_merge($mach, ['tenant_id' => $tenantId])
            );
        }

        // 3. Usuários Padrão para Testes no App e Painel Web
        $usersData = [
            [
                'email' => 'admin@amemiya.com',
                'name' => 'Administrador Amemiya',
                'password' => Hash::make('12345678'),
            ],
            [
                'email' => 'metrologista@amemiya.com',
                'name' => 'Carlos Metrologista',
                'password' => Hash::make('12345678'),
            ],
            [
                'email' => 'operador@amemiya.com',
                'name' => 'João Operador',
                'password' => Hash::make('12345678'),
            ],
        ];

        $users = [];
        foreach ($usersData as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => $u['password'],
                    'tenant_id' => $tenantId,
                    'terms_accepted_at' => now(),
                    'privacy_policy_accepted_at' => now(),
                ]
            );

            // Garante acesso com papel de super-admin
            $user->syncRoles(['super-admin']);
            $users[] = $user;
        }

        // 4. Competências Metrológicas (ISO/IEC 17025) para o usuário técnico e admin
        $instrumentTypes = InstrumentType::all();
        if ($instrumentTypes->isNotEmpty()) {
            foreach ([$users[0], $users[1]] as $qualUser) {
                foreach ($instrumentTypes as $type) {
                    DB::table('competences')->updateOrInsert(
                        [
                            'user_id' => $qualUser->id,
                            'instrument_type_id' => $type->id,
                        ],
                        [
                            'valid_until' => now()->addYears(2)->toDateString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
