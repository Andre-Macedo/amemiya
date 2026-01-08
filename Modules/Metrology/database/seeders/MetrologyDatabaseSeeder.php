<?php

namespace Modules\Metrology\Database\Seeders;

use App\Models\Station;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\ChecklistTemplateItem;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Models\ReferenceStandardType;

class MetrologyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
// Modules/Metrology/database/seeders/MetrologyDatabaseSeeder.php

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        ChecklistItem::truncate();
        Checklist::truncate();
        Station::truncate();
        Supplier::truncate();
        Calibration::truncate();
        Instrument::truncate();
        ChecklistTemplateItem::truncate();
        ChecklistTemplate::truncate();
        InstrumentType::truncate();
        ReferenceStandard::truncate();
        ReferenceStandardType::truncate();
        Schema::enableForeignKeyConstraints();

        // -- 1. Create Users --
        $users = collect([
            User::firstOrCreate(['email' => 'tech1@example.com'], ['name' => 'Técnico Metrologista 1', 'password' => bcrypt('password')]),
            User::firstOrCreate(['email' => 'tech2@example.com'], ['name' => 'Técnico Metrologista 2', 'password' => bcrypt('password')]),
        ]);

        // -- 2. FORNECEDORES (Suppliers) --
        $mitutoyo = Supplier::create([
            'name' => 'Mitutoyo Sul Americana',
            'trade_name' => 'Mitutoyo',
            'is_manufacturer' => true,
            'is_calibration_provider' => true, // Eles também calibram
            'is_maintenance_provider' => true,
            'rbc_code' => '00123',
            'accreditation_valid_until' => '2030-12-31'
        ]);

        $starrett = Supplier::create([
            'name' => 'Starrett Indústria',
            'is_manufacturer' => true,
            'is_calibration_provider' => false,
        ]);

        $labExterno = Supplier::create([
            'name' => 'Laboratório Padrão RBC',
            'trade_name' => 'LabPadrão',
            'is_manufacturer' => false,
            'is_calibration_provider' => true,
            'rbc_code' => '00555',
            'accreditation_valid_until' => now()->addYear(),
        ]);

        $manufacturers = collect([$mitutoyo, $starrett]);

        // -- 3. ESTAÇÕES (Stations) --
        $labInternal = Station::create([
            'name' => 'Laboratório Central',
            'type' => 'internal_lab',
            'location' => 'Prédio ADM - Térreo',
        ]);

        $labExternal = Station::create([
            'name' => 'Externo (Em Trânsito)',
            'type' => 'external_provider',
            'location' => 'Fornecedor',
        ]);

        $almoxarifado = Station::create([
            'name' => 'Almoxarifado Geral',
            'type' => 'storage',
            'location' => 'Galpão 1',
        ]);

        $linhaProducao = Station::create([
            'name' => 'Linha de Montagem 01',
            'type' => 'general', // Produção
            'location' => 'Chão de Fábrica',
        ]);

        // ==================================================
        // 4. PADRÕES DE REFERÊNCIA (Tipos e Itens)
        // ==================================================
        $typeBlock = ReferenceStandardType::create(['name' => 'Bloco Padrão', 'calibration_frequency_months' => 24]);
        $typeRuler = ReferenceStandardType::create(['name' => 'Régua Graduada', 'calibration_frequency_months' => 12]);
        $typeWeight = ReferenceStandardType::create(['name' => 'Peso Padrão', 'calibration_frequency_months' => 12]);
        $typeCalibrator = ReferenceStandardType::create(['name' => 'Calibrador Universal', 'calibration_frequency_months' => 24]);
        $typeBlockSet = ReferenceStandardType::create([
            'name' => 'Jogo de Blocos (Kit)',
            'calibration_frequency_months' => 24
        ]);

        $calibratorMesa = ReferenceStandard::create([
            'name' => 'Calibrador de Relógio (Mesa Micrométrica)',
            'stock_number' => 'CAL-001',
            'reference_standard_type_id' => $typeCalibrator->id,
            'calibration_due' => now()->addMonths(12),
        ]);

        // --- CENÁRIO A: KIT DE BLOCOS (Gauge Block Set) ---
        $kitBlocos = ReferenceStandard::create([
            'name' => 'Jogo de Blocos Padrão (47 Peças)',
            'stock_number' => 'JB-001',
            'serial_number' => 'SN-KIT-999',
            'reference_standard_type_id' => $typeBlockSet->id,
            'calibration_due' => now()->addMonths(12),
            'is_kit' => true, // Se tiver criado a coluna na migration
        ]);

        // Criar os Filhos (Peças do Kit usadas nos checklists)
        $blocosValues = [1.005, 2.5, 5.0, 10.0, 25.0, 50.0, 75.0, 100.0];

        foreach ($blocosValues as $val) {
            ReferenceStandard::create([
                'parent_id' => $kitBlocos->id, // Vínculo vital
                'reference_standard_type_id' => $typeBlock->id,
                'name' => "Bloco {$val}mm",
                'nominal_value' => $val,
                'actual_value' => $val + 0.0002, // Erro simulado
                'uncertainty' => 0.0001,
                'unit' => 'mm',
                'calibration_due' => $kitBlocos->calibration_due, // Herda data
            ]);
        }

        // --- CENÁRIO B: RÉGUA MULTI-PONTO ---
        $regua = ReferenceStandard::create([
            'name' => 'Régua de Aço Inox 300mm',
            'stock_number' => 'REG-050',
            'reference_standard_type_id' => $typeRuler->id,
            'calibration_due' => now()->subDays(10), // VENCIDA (Para testar alerta)
            'is_kit' => true,
        ]);

        $pontosRegua = [50, 100, 150, 200, 250, 300];
        foreach ($pontosRegua as $ponto) {
            ReferenceStandard::create([
                'parent_id' => $regua->id,
                'reference_standard_type_id' => $typeRuler->id,
                'name' => "Ponto {$ponto}mm",
                'nominal_value' => $ponto,
                'actual_value' => $ponto - 0.05,
                'uncertainty' => 0.02,
                'unit' => 'mm',
                'calibration_due' => $regua->calibration_due,
            ]);
        }

        // -- 3. Create Reference Standards --
        $referenceStandards = collect();

        // Helper para criar padrões já com data de vencimento calculada
        $createStandards = function($type, $count) use (&$referenceStandards) {
            $standards = ReferenceStandard::factory()->count($count)->create([
                'reference_standard_type_id' => $type->id,
                'calibration_due' => now()->addMonths($type->calibration_frequency_months),
            ]);
            $referenceStandards = $referenceStandards->merge($standards);
        };

        $createStandards($typeBlock, 5);
        $createStandards($typeWeight, 2);

        // -- 4. Create Instrument Types (Com frequências corretas) --
        $typePaqAnalog = InstrumentType::create(['name' => 'Paquímetro Analógico', 'calibration_frequency_months' => 12]);
        $typePaqDigital = InstrumentType::create(['name' => 'Paquímetro Digital', 'calibration_frequency_months' => 6]);

        $typeMicroAnalog = InstrumentType::create(['name' => 'Micrômetro Analógico', 'calibration_frequency_months' => 12]);
        $typeMicroDigital = InstrumentType::create(['name' => 'Micrômetro Digital', 'calibration_frequency_months' => 9]);

        $typeRelogio = InstrumentType::create(['name' => 'Relógio Comparador', 'calibration_frequency_months' => 12]);

        $bloco25 = ReferenceStandard::where('nominal_value', 25.0)->whereNotNull('parent_id')->first();
        $bloco50 = ReferenceStandard::where('nominal_value', 50.0)->whereNotNull('parent_id')->first();
        $bloco100 = ReferenceStandard::where('nominal_value', 100.0)->whereNotNull('parent_id')->first();



        // -- 5. Create Checklist Templates (Específicos para cada tecnologia) --

        // 5.1 Paquímetro Analógico
        $checkPaqAnalog = ChecklistTemplate::factory()->create(['name' => 'Rotina: Paquímetro Analógico', 'instrument_type_id' => $typePaqAnalog->id]);
        $checkPaqAnalog->items()->createMany([
            ['step' => 'Limpeza das faces de medição', 'question_type' => 'boolean', 'order' => 1],
            ['step' => 'Verificação de folga no cursor', 'question_type' => 'boolean', 'order' => 2],
            ['step' => 'Inspeção visual da escala (desgaste)', 'question_type' => 'boolean', 'order' => 3],
            [
                'order' => 4, 'step' => 'Medição 25mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 25.00,
            ],
            [
                'order' => 5, 'step' => 'Medição 50mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 50.00,
            ],
            [
                'order' => 6, 'step' => 'Medição 100mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 100.00,
            ],
        ]);

        // 5.2 Paquímetro Digital (Com bateria e zeragem)
        $checkPaqDigital = ChecklistTemplate::factory()->create(['name' => 'Rotina: Paquímetro Digital', 'instrument_type_id' => $typePaqDigital->id]);
        $checkPaqDigital->items()->createMany([
            ['step' => 'Limpeza das faces de medição', 'question_type' => 'boolean', 'order' => 1],
            ['step' => 'Verificar estado da bateria/contatos', 'question_type' => 'boolean', 'order' => 2],
            ['step' => 'Teste de funcionamento dos botões', 'question_type' => 'boolean', 'order' => 3],
            ['step' => 'Zerar o instrumento (Set Zero)', 'question_type' => 'boolean', 'order' => 4],
            [
                'order' => 5, 'step' => 'Medição 25mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 25.00,
            ],
            [
                'order' => 6, 'step' => 'Medição 50mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 50.00,
            ],
            [
                'order' => 7, 'step' => 'Medição 100mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 100.00,
            ],
        ]);

        // 5.3 Micrômetro Analógico
        $checkMicroAnalog = ChecklistTemplate::factory()->create(['name' => 'Rotina: Micrômetro Analógico', 'instrument_type_id' => $typeMicroAnalog->id]);
        $checkMicroAnalog->items()->createMany([
            ['step' => 'Limpeza das pontas de contato', 'question_type' => 'boolean', 'order' => 1],
            ['step' => 'Verificar funcionamento da catraca', 'question_type' => 'boolean', 'order' => 2],
            ['step' => 'Verificar paralelismo visual', 'question_type' => 'boolean', 'order' => 3],
            [
                'order' => 4, 'step' => 'Medição 25mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 25.00,
            ],
            [
                'order' => 5, 'step' => 'Medição 50mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 50.00,
            ],
            [
                'order' => 6, 'step' => 'Medição 100mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 100.00,
            ],
        ]);

        // 5.4 Micrômetro Digital
        $checkMicroDigital = ChecklistTemplate::factory()->create(['name' => 'Rotina: Micrômetro Digital', 'instrument_type_id' => $typeMicroDigital->id]);
        $checkMicroDigital->items()->createMany([
            ['step' => 'Limpeza das pontas de contato', 'question_type' => 'boolean', 'order' => 1],
            ['step' => 'Verificar carga da bateria', 'question_type' => 'boolean', 'order' => 2],
            ['step' => 'Verificar funcionamento da catraca', 'question_type' => 'boolean', 'order' => 3],
            ['step' => 'Zerar instrumento (Abs/Inc)', 'question_type' => 'boolean', 'order' => 4],
            [
                'order' => 5, 'step' => 'Medição 25mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 25.00
            ],
            [
                'order' => 6, 'step' => 'Medição 50mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 50.00,
            ],
            [
                'order' => 7, 'step' => 'Medição 100mm', 'question_type' => 'numeric', 'required_readings' => 3,
                'reference_standard_type_id' => $typeBlock->id, 'nominal_value' => 100.00,
            ],
        ]);

        // 5.5 Relógio Comparador
        $checkRelogio = ChecklistTemplate::factory()->create(['name' => 'Rotina: Relógio Comparador', 'instrument_type_id' => $typeRelogio->id]);
        $checkRelogio->items()->createMany([
            ['step' => 'Verificação visual do mostrador/vidro', 'question_type' => 'boolean', 'order' => 1],
            ['step' => 'Suavidade do movimento da haste', 'question_type' => 'boolean', 'order' => 2],
            ['step' => 'Retorno do ponteiro ao zero', 'question_type' => 'boolean', 'order' => 3],
            ['step' => 'Verificar ponta de contato (desgaste)', 'question_type' => 'boolean', 'order' => 4],
            // Teste simples de repetibilidade
            ['step' => 'Teste de repetibilidade (10x no mesmo ponto)', 'question_type' => 'numeric', 'order' => 5, 'required_readings' => 5, 'reference_standard_type_id' => $typeCalibrator->id],
        ]);

        // -- 6. Create Instruments (Lógica Realista de Status e Datas) --
        $instruments = collect();

        $createBatch = function($prefix, $count, $typesWithError) use (&$instruments, $manufacturers, $labInternal, $labExternal, $linhaProducao, $almoxarifado) {
            for ($i = 1; $i <= $count; $i++) {
                $stockNumber = sprintf('%s-%03d', $prefix, $i);

                // Escolhe tipo
                $typeId = fake()->randomElement(array_keys($typesWithError));
                $type = \Modules\Metrology\Models\InstrumentType::find($typeId);
                $uncertainty = $typesWithError[$typeId];

                // SORTEIO DO CENÁRIO DO INSTRUMENTO
                $scenario = fake()->randomElement(['active', 'active', 'due_soon', 'expired', 'in_calibration', 'maintenance', 'rejected']);

                $status = 'active';
                $stationId = $linhaProducao->id; // Default: Está na linha
                $dueDate = now()->addMonths($type->calibration_frequency_months);

                if ($scenario === 'expired') {
                    $status = 'expired'; // Fica na linha, mas vencido
                    $dueDate = now()->subDays(rand(10, 60));
                }
                elseif ($scenario === 'rejected') {
                    $status = 'rejected';
                    $stationId = $labInternal->id; // Segregado no lab
                    // Data vencida/passada (data da reprovação)
                    $dueDate = now()->subDays(rand(1, 30));
                }
                elseif ($scenario === 'in_calibration') {
                    $status = 'in_calibration';
                    $stationId = $labInternal->id; // Está fisicamente no lab
                    $dueDate = now()->subDays(2); // Venceu e foi pro lab
                }
                elseif ($scenario === 'maintenance') {
                    $status = 'maintenance';
                    $stationId = $labInternal->id; // Ou oficina externa
                    $dueDate = now()->subDays(30);
                }
                elseif ($scenario === 'due_soon') {
                    $dueDate = now()->addDays(random_int(1, 20));
                }

                $instrument = \Modules\Metrology\Models\Instrument::factory()->create([
                    'instrument_type_id' => $type->id,
                    'name' => $type->name . ' ' . $i,
                    'stock_number' => $stockNumber,
                    'uncertainty' => $typesWithError[$typeId],
                    'manufacturer' => fake()->randomElement(['Mitutoyo', 'Starrett', 'Digimess', 'Tesa']),
                    'status' => $status,
                    'calibration_due' => $dueDate,
                    'current_station_id' => $stationId,
                ]);

                $instruments->push($instrument);
            }
        };

        // A. Paquímetros (PQ) - 85 un.
        $createBatch('PQ', 85, [
            $typePaqAnalog->id => '0.05mm',
            $typePaqDigital->id => '0.01mm'
        ]);

        // B. Micrômetros (MC) - 119 un.
        $createBatch('MC', 119, [
            $typeMicroAnalog->id => '0.01mm',
            $typeMicroDigital->id => '0.001mm'
        ]);

        // C. Relógios (RC) - 251 un.
        $createBatch('RC', 251, [
            $typeRelogio->id => '0.001mm'
        ]);

        // -- 7. Create Calibrations (Agora usando os novos templates) --
        foreach ($instruments as $instrument) {
            Calibration::withoutEvents(function () use ($instrument, $users, $referenceStandards, $labExterno, $kitBlocos, $calibratorMesa) {
                $freq = $instrument->instrumentType->calibration_frequency_months ?? 12;
                $numberOfCalibrations = rand(1, 3);

                for ($i = 0; $i < $numberOfCalibrations; $i++) {
                    $isLatest = ($i == 0);
                    $result = 'approved';

                    // Lógica para determinar a data
                    if ($isLatest && $instrument->status === 'rejected') {
                        // Se o instrumento está REJEITADO atualmente, a última calibração TEM que ser rejeitada
                        $result = 'rejected';
                        $date = $instrument->calibration_due; // A data da rejeição é a data do vencimento/atual
                    } else {
                        // Histórico normal (aprovado)
                        $date = $instrument->calibration_due->copy()->subMonths($freq * ($i + ($instrument->status === 'active' ? 1 : 0)));
                    }

                    // CORREÇÃO 1: Se a data calculada for futura, não cria histórico (pula)
                    if ($date->isFuture()) {
                        continue;
                    }

                    $isExternal = fake()->boolean(30);
                    $isInternal = !$isExternal; // CORREÇÃO 2: Define explicitamente

                    $calibration = Calibration::factory()->create([
                        'calibrated_item_id' => $instrument->id,
                        'calibrated_item_type' => Instrument::class,
                        'type' => $isExternal ? 'external_rbc' : 'internal',
                        'provider_id' => $isExternal ? $labExterno->id : null,
                        'calibration_date' => $date,
                        'result' => $result,
                        // Se rejeitado, força um desvio alto. Se aprovado, desvio baixo.
                        'deviation' => $result === 'rejected' ? 0.99 : 0.001,
                        'performed_by_id' => $users->random()->id,
                        'certificate_path' => $isExternal ? 'fake/cert.pdf' : null,
                    ]);

                    // CORREÇÃO 3: Checklist apenas para Calibrações INTERNAS e APROVADAS
                    // (Para simplificar o seed e não quebrar a cabeça gerando falhas no checklist)
                    if ($isInternal && $result === 'approved' && $instrument->instrumentType) {
                        $template = $instrument->instrumentType->checklistTemplates()->first();

                        if ($template) {
                            $checklist = Checklist::create([
                                'calibration_id' => $calibration->id,
                                'checklist_template_id' => $template->id,
                                'completed' => true,
                            ]);

                            foreach ($template->items as $templateItem) {
                                // LÓGICA DE BUSCA DO PADRÃO (Já que removemos o ID fixo do template)
                                $stdId = null;

                                if ($templateItem->question_type === 'numeric') {
                                    // 1. Se tem nominal, busca no Kit de Blocos
                                    if ($templateItem->nominal_value) {
                                        $stdId = ReferenceStandard::where('parent_id', $kitBlocos->id)
                                            ->where('nominal_value', $templateItem->nominal_value)
                                            ->first()?->id;
                                    }
                                    // 2. Se pede Calibrador, usa a Mesa
                                    elseif ($templateItem->reference_standard_type_id === $calibratorMesa->reference_standard_type_id) {
                                        $stdId = $calibratorMesa->id;
                                    }
                                }
                                $checklist->items()->create([
                                    'checklist_id' => $checklist->id,
                                    'step' => $templateItem->step,
                                    'question_type' => $templateItem->question_type,
                                    'order' => $templateItem->order,
                                    'completed' => true,
                                    'result' => 'approved',
                                    'required_readings' => $templateItem->required_readings,
                                    'reference_standard_id' => $templateItem->reference_standard_id,
                                    'readings' => $templateItem->question_type === 'numeric'
                                        ? json_encode([
                                            $templateItem->nominal_value + 0.01,
                                            $templateItem->nominal_value,
                                            $templateItem->nominal_value + 0.01
                                        ])
                                        : null,
                                ]);
                            }
                            $calibration->update(['checklist_id' => $checklist->id]);
                        }
                    }
                }
            });
        }

        // -- 8. Calibração dos Padrões (Mantido igual) --
        $standardsToCalibrate = collect([$kitBlocos, $regua]);

        foreach ($standardsToCalibrate as $std) {
            Calibration::factory()->create([
                'calibrated_item_id' => $std->id,
                'calibrated_item_type' => ReferenceStandard::class,
                'type' => 'external_rbc',
                'provider_id' => $labExterno->id,
                'result' => 'approved',
                // Data baseada no vencimento (para bater com o status active/expired)
                'calibration_date' => $std->calibration_due->copy()->subMonths(
                    $std->referenceStandardType->calibration_frequency_months
                ),
            ]);
        }
    }}
