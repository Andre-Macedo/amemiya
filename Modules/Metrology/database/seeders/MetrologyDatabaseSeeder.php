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

        $users = collect([
            User::firstOrCreate(['email' => 'tech1@example.com'], ['name' => 'Tech One', 'password' => bcrypt('password')]),
            User::firstOrCreate(['email' => 'tech2@example.com'], ['name' => 'Tech Two', 'password' => bcrypt('password')]),
        ]);

        $refTypeBlock = ReferenceStandardType::create(['name' => 'Bloco Padrão']);
        $refTypeCalibrator = ReferenceStandardType::create(['name' => 'Calibrador']);
        $refTypeWeight = ReferenceStandardType::create(['name' => 'Peso Padrão']);

        $referenceStandards = collect();
        $referenceStandards = $referenceStandards->merge(ReferenceStandard::factory()->count(5)->create(['reference_standard_type_id' => $refTypeBlock->id]));
        $referenceStandards = $referenceStandards->merge(ReferenceStandard::factory()->count(5)->create(['reference_standard_type_id' => $refTypeCalibrator->id]));
        $referenceStandards = $referenceStandards->merge(ReferenceStandard::factory()->count(5)->create(['reference_standard_type_id' => $refTypeWeight->id]));

        $typePaquimetro = InstrumentType::create(['name' => 'Paquímetro']);
        $typeMicrometro = InstrumentType::create(['name' => 'Micrômetro']);
        $typeMultimetro = InstrumentType::create(['name' => 'Multímetro']);

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

        $instruments = collect();
        $instruments = $instruments->merge(Instrument::factory()->count(10)->create(['instrument_type_id' => $typePaquimetro->id]));
        $instruments = $instruments->merge(Instrument::factory()->count(10)->create(['instrument_type_id' => $typeMicrometro->id]));
        $instruments = $instruments->merge(Instrument::factory()->count(10)->create(['instrument_type_id' => $typeMultimetro->id]));

        foreach ($instruments as $instrument) {
            Calibration::factory()->count(rand(1, 3))->create([
                'instrument_id' => $instrument->id,
                'performed_by_id' => $users->random()->id,
            ])
                ->each(function (Calibration $calibration) use ($referenceStandards) {

                    if ($calibration->type === 'internal' && $calibration->instrument->instrumentType) {
                        $template = $calibration->instrument->instrumentType->checklistTemplates()->inRandomOrder()->first();

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
                                    for ($i = 0; $i < $templateItem->required_readings; $i++) {
                                        $readings[] = round(25 + fake()->randomFloat(3, -0.05, 0.05), 3);
                                    }
                                    $data['readings'] = $readings;
                                    $data['uncertainty'] = fake()->randomFloat(4, 0.001, 0.005);

                                    // CORRIGIDO: Associa um padrão de referência real ao item do checklist
                                    if ($templateItem->reference_standard_type_id) {
                                        $data['reference_standard_id'] = $referenceStandards
                                            ->where('reference_standard_type_id', $templateItem->reference_standard_type_id)
                                            ->random()->id;
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
                });
        }
    }
}
