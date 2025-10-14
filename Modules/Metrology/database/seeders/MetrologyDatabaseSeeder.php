<?php

namespace Modules\Metrology\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
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
        // -- 0. Nuke Old Data --
        // Disable foreign key checks to allow truncating tables in any order.
        Schema::disableForeignKeyConstraints();

        // Truncate all tables related to this module to ensure a clean slate.
        Calibration::truncate();
        Instrument::truncate();
        ChecklistTemplateItem::truncate();
        Checklist::truncate();
        ChecklistItem::truncate();
        ChecklistTemplate::truncate(); // And its items table if separate
        InstrumentType::truncate();
        ReferenceStandard::truncate();
        ReferenceStandardType::truncate();
        // Add any other module-specific tables here...
        // DB::table('calibration_reference_standard')->truncate(); // Example for pivot table

        // Re-enable foreign key checks.
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

        // -- 7. Create Calibrations and Filled Checklists --
        foreach ($instruments as $instrument) {
            Calibration::factory()->count(rand(1, 5))->create([
                'instrument_id' => $instrument->id,
                'performed_by' => $users->random()->id,
            ])
                ->each(function (Calibration $calibration) use ($referenceStandards) {
                    // Attach reference standards
                    $calibration->referenceStandards()->attach(
                        $referenceStandards->random(rand(1, 3))->pluck('id')->toArray()
                    );

                    // **NEW: Create a filled checklist for internal calibrations**
                    if ($calibration->type === 'internal' && $calibration->instrument->instrumentType) {
                        $template = $calibration->instrument->instrumentType->checklistTemplates()->first();

                        if ($template) {
                            $checklist = Checklist::create([
                                'calibration_id' => $calibration->id,
                                'checklist_template_id' => $template->id,
                                'completed' => true, // Assume it's completed for seeded data
                            ]);

                            // Create an item for each template item
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
                                    for ($i = 0; $i < $templateItem->required_readings; $i++) {
                                        $readings[] = round(25 + fake()->randomFloat(3, -0.05, 0.05), 3);
                                    }
                                    $data['readings'] = $readings;
                                    $data['uncertainty'] = fake()->randomFloat(4, 0.001, 0.005);
                                }

                                if ($templateItem->question_type === 'text') {
                                    $data['notes'] = fake()->sentence();
                                }

                                $checklist->items()->create($data);
                            }

                            // Link checklist back to calibration
                            $calibration->update(['checklist_id' => $checklist->id]);
                        }
                    }
                });
        }
    }
}
