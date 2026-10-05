<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Actions\CreateCalibrationAction;
use Modules\Metrology\Actions\ProcessCalibrationAction;
use Modules\Metrology\DTOs\CalibrationSubmissionDTO;
use Modules\Metrology\Http\Resources\InstrumentApiResource;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\ChecklistTemplateItem;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Modules\System\Models\User;
use Tests\TestCase;

class CalibrationChecklistPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_persists_checklist_items_with_as_found_and_as_left_readings_via_action(): void
    {
        $type = InstrumentType::factory()->create(['name' => 'Micrômetro Externo']);
        $instrument = Instrument::factory()->create([
            'instrument_type_id' => $type->id,
            'resolution' => '0.001',
        ]);

        $template = ChecklistTemplate::factory()->create([
            'name' => 'POP-01 Micrômetro',
            'instrument_type_id' => $type->id,
            'is_active' => true,
        ]);

        $templateItem1 = ChecklistTemplateItem::factory()->create([
            'checklist_template_id' => $template->id,
            'step' => 'Ponto 25 mm',
            'question_type' => 'numeric',
            'nominal_value' => 25.00,
            'required_readings' => 3,
            'order' => 1,
        ]);

        $templateItem2 = ChecklistTemplateItem::factory()->create([
            'checklist_template_id' => $template->id,
            'step' => 'Limpeza das faces de medição',
            'question_type' => 'boolean',
            'order' => 2,
        ]);

        $dto = new CalibrationSubmissionDTO(
            instrumentId: (string) $instrument->id,
            date: '2026-10-05',
            result: 'approved',
            deviation: 0.002,
            uncertainty: 0.0015,
            temperature: 20.0,
            humidity: 50.0,
            notes: 'Calibração realizada em bancada climatizada',
            templateId: (string) $template->id,
            items: [
                [
                    'template_item_id' => (string) $templateItem1->id,
                    'as_found_readings' => [25.003, 25.002, 25.004],
                    'as_left_readings' => [25.001, 25.000, 25.001],
                    'adjusted' => true,
                ],
                [
                    'template_item_id' => (string) $templateItem2->id,
                    'result' => 'pass',
                ],
            ],
            performedBy: (string) $this->user->id,
            asFoundResult: 'conditional',
            asLeftResult: 'approved',
            asFoundDeviation: 0.004,
            asLeftDeviation: 0.001
        );

        $action = new CreateCalibrationAction();
        $calibration = $action->execute($dto);

        // 1. Verifica integridade da calibração
        $this->assertNotNull($calibration->id);
        $this->assertNotNull($calibration->checklist_id);

        // 2. Verifica os itens do checklist gravados no banco
        $checklist = $calibration->checklist;
        $this->assertNotNull($checklist);
        $this->assertEquals((string) $calibration->id, (string) $checklist->calibration_id);
        $this->assertCount(2, $checklist->items);

        $numericItem = $checklist->items()->where('question_type', 'numeric')->first();
        $this->assertNotNull($numericItem);
        $this->assertNotNull($numericItem->id); // Garante que o ULID foi gerado
        $this->assertEquals(25.0, (float) $numericItem->nominal_value);
        $this->assertEquals([25.003, 25.002, 25.004], $numericItem->as_found_readings);
        $this->assertEquals([25.001, 25.000, 25.001], $numericItem->as_left_readings);
        $this->assertTrue((bool) $numericItem->adjusted);
        $this->assertTrue((bool) $numericItem->completed);

        // 3. Testa o alias template() e o snapshot de procedimento em ProcessCalibrationAction
        $this->assertNotNull($checklist->template);
        $this->assertEquals($template->id, $checklist->template->id);

        $processAction = new ProcessCalibrationAction();
        $processAction->execute($calibration);
        $calibration->refresh();

        $this->assertNotNull($calibration->procedure_snapshot);
        $this->assertIsArray($calibration->procedure_snapshot['template']);
        $this->assertEquals((string) $template->id, $calibration->procedure_snapshot['template']['id']);
        $this->assertEquals('POP-01 Micrômetro', $calibration->procedure_snapshot['template']['name']);
    }

    public function test_instrument_api_resource_exposes_default_checklist_template(): void
    {
        $type = InstrumentType::factory()->create(['name' => 'Paquímetro Digital']);
        $template = ChecklistTemplate::factory()->create([
            'name' => 'POP-02 Paquímetro',
            'instrument_type_id' => $type->id,
            'is_active' => true,
            'version' => 2,
        ]);
        ChecklistTemplateItem::factory()->count(3)->create([
            'checklist_template_id' => $template->id,
        ]);

        $instrument = Instrument::factory()->create([
            'instrument_type_id' => $type->id,
        ]);

        $resource = (new InstrumentApiResource($instrument->load('instrumentType')))->toArray(request());

        $this->assertArrayHasKey('default_checklist_template', $resource);
        $this->assertNotNull($resource['default_checklist_template']);
        $this->assertEquals((string) $template->id, $resource['default_checklist_template']['id']);
        $this->assertEquals('POP-02 Paquímetro', $resource['default_checklist_template']['name']);
        $this->assertEquals(2, $resource['default_checklist_template']['version']);
        $this->assertEquals(3, $resource['default_checklist_template']['items_count']);
    }
}
