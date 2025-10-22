<?php

namespace Modules\Metrology\Database\Seeders;

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
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        ChecklistItem::truncate();
        Checklist::truncate();
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
            User::firstOrCreate(['email' => 'tech1@example.com'], ['name' => 'Tech One', 'password' => bcrypt('password')]),
            User::firstOrCreate(['email' => 'tech2@example.com'], ['name' => 'Tech Two', 'password' => bcrypt('password')]),
        ]);

        // -- 2. Create Reference Standard Types --
        $refTypeBlock = ReferenceStandardType::create(['name' => 'Bloco Padrão']);
        $refTypeCalibrator = ReferenceStandardType::create(['name' => 'Calibrador']);
        $refTypeWeight = ReferenceStandardType::create(['name' => 'Peso Padrão']);

        // -- 3. Create Reference Standards --
        $referenceStandards = collect();
        $referenceStandards = $referenceStandards->merge(ReferenceStandard::factory()->count(5)->create(['reference_standard_type_id' => $refTypeBlock->id]));
        $referenceStandards = $referenceStandards->merge(ReferenceStandard::factory()->count(5)->create(['reference_standard_type_id' => $refTypeCalibrator->id]));
        $referenceStandards = $referenceStandards->merge(ReferenceStandard::factory()->count(5)->create(['reference_standard_type_id' => $refTypeWeight->id]));

        // -- 4. Create Instrument Types --
        $typePaquimetro = InstrumentType::create(['name' => 'Paquímetro']);
        $typeMicrometro = InstrumentType::create(['name' => 'Micrômetro']);
        $typeMultimetro = InstrumentType::create(['name' => 'Multímetro']);

        // -- 5. Create Checklist Templates --
        $checklistPaquimetro = ChecklistTemplate::factory()->create(['name' => 'Checklist Padrão para Paquímetro', 'instrument_type_id' => $typePaquimetro->id]);
        $checklistPaquimetro->items()->createMany([
            ['step' => 'Verificação visual do instrumento', 'question_type' => 'boolean', 'order' => 1],
            ['step' => 'Limpeza das faces de medição', 'question_type' => 'boolean', 'order' => 2],
            ['step' => 'Medição do bloco de 25mm', 'question_type' => 'numeric', 'order' => 3, 'required_readings' => 3, 'reference_standard_type_id' => $refTypeBlock->id],
        ]);

        $checklistMicrometro = ChecklistTemplate::factory()->create(['name' => 'Checklist Padrão para Micrômetro', 'instrument_type_id' => $typeMicrometro->id]);
        $checklistMicrometro->items()->createMany([
            ['step' => 'Verificação visual do instrumento', 'question_type' => 'boolean', 'order' => 1],
            ['step' => 'Zerar o instrumento', 'question_type' => 'boolean', 'order' => 2],
            ['step' => 'Medição do bloco de 50mm', 'question_type' => 'numeric', 'order' => 3, 'required_readings' => 5, 'reference_standard_type_id' => $refTypeBlock->id],
        ]);

        // -- 6. Create Instruments --
        $instruments = collect();
        $instruments = $instruments->merge(Instrument::factory()->count(10)->create(['instrument_type_id' => $typePaquimetro->id]));
        $instruments = $instruments->merge(Instrument::factory()->count(10)->create(['instrument_type_id' => $typeMicrometro->id]));
        $instruments = $instruments->merge(Instrument::factory()->count(10)->create(['instrument_type_id' => $typeMultimetro->id]));

        // -- 7. Create Calibrations for Instruments --
        foreach ($instruments as $instrument) {
            // Criar algumas calibrações internas e externas para cada instrumento
            $numberOfCalibrations = rand(1, 3);
            for ($i = 0; $i < $numberOfCalibrations; $i++) {
                $isInternal = fake()->boolean(70); // 70% chance de ser interna

                $calibration = Calibration::factory()->create([
                    // Usa a relação polimórfica
                    'calibrated_item_id' => $instrument->id,
                    'calibrated_item_type' => Instrument::class,
                    'type' => $isInternal ? 'internal' : 'external_rbc',
                    'performed_by_id' => $users->random()->id,
                    // Simula um certificado para calibrações externas
                    'certificate_path' => !$isInternal ? 'fake/certificate_' . uniqid('', true) . '.pdf' : null,
                ]);

                // Se for interna, cria o checklist preenchido
                if ($isInternal && $instrument->instrumentType) {
                    $template = $instrument->instrumentType->checklistTemplates()->inRandomOrder()->first();

                    if ($template) {
                        $checklist = Checklist::create([
                            'calibration_id' => $calibration->id,
                            'checklist_template_id' => $template->id,
                            'completed' => true,
                        ]);

                        foreach ($template->items as $templateItem) {
                            $data = [
                                'checklist_id' => $checklist->id,
                                'step' => $templateItem->step,
                                'question_type' => $templateItem->question_type,
                                'order' => $templateItem->order,
                                'completed' => true,
                                'result' => 'approved',
                            ];

                            if ($templateItem->question_type === 'numeric') {
                                $readings = [];
                                for ($r = 0; $r < $templateItem->required_readings; $r++) {
                                    $readings[] = round(25 + fake()->randomFloat(3, -0.05, 0.05), 3);
                                }
                                $data['readings'] = $readings;
                                $data['uncertainty'] = fake()->randomFloat(4, 0.001, 0.005);

                                if ($templateItem->reference_standard_type_id) {
                                    $availableStandards = $referenceStandards
                                        ->where('reference_standard_type_id', $templateItem->reference_standard_type_id);
                                    if ($availableStandards->isNotEmpty()) {
                                        $data['reference_standard_id'] = $availableStandards->random()->id;
                                    }
                                }
                            }

                            if ($templateItem->question_type === 'text') {
                                $data['notes'] = fake()->sentence();
                            }

                            $checklist->items()->create($data);
                        }
                        $calibration->update(['checklist_id' => $checklist->id]);
                    }
                }
            }
        }

        // -- 8. Create External Calibrations for Reference Standards --
        foreach ($referenceStandards as $standard) {
            // Cria 1 ou 2 registos de calibração externa para cada padrão
            $numberOfCalibrations = rand(1, 2);
            for ($i = 0; $i < $numberOfCalibrations; $i++) {
                Calibration::factory()->create([
                    // Usa a relação polimórfica
                    'calibrated_item_id' => $standard->id,
                    'calibrated_item_type' => ReferenceStandard::class,
                    'type' => 'external_rbc', // Padrões só têm calibração externa
                    'performed_by_id' => $users->random()->id, // Simula quem registou
                    'certificate_path' => 'fake/standard_certificate_' . uniqid('', true) . '.pdf', // Simula o certificado anexo
                    'result' => 'approved', // Assume que a calibração do padrão foi aprovada
                ]);
            }
        }
    }
}
