<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\ChecklistTemplateItem;
use Modules\Metrology\Models\InstrumentType;
use Tests\Concerns\HasSuperAdmin;

uses(RefreshDatabase::class, HasSuperAdmin::class);

it('can create a checklist template with items', function () {
    $this->createSuperAdmin();
    $instrumentType = InstrumentType::factory()->create(['name' => 'Micrometer']);

    // 1. Cria Modelo (Template)
    $template = ChecklistTemplate::factory()->create([
        'name' => 'Standard Micrometer Checks',
        'instrument_type_id' => $instrumentType->id, // Assumindo relação via ID
    ]);

    // 2. Cria Itens
    $item1 = ChecklistTemplateItem::factory()->create([
        'checklist_template_id' => $template->id,
        'step' => 'Visual Inspection',
        'order' => 1,
    ]);

    $item2 = ChecklistTemplateItem::factory()->create([
        'checklist_template_id' => $template->id,
        'step' => 'Zero Check',
        'order' => 2,
    ]);

    expect($template->items)->toHaveCount(2);
    expect($template->items->first()->step)->toBe('Visual Inspection');
});
