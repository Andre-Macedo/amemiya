<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Metrology\Models\ChecklistTemplate;

/**
 * Manages the creation and updating of checklist templates and their nested items.
 */
class SaveChecklistTemplateAction
{
    /**
     * Saves a checklist template with its items in a single transaction.
     *
     * Args:
     *     data: The validated template data array.
     *     template: Optional template instance for updates.
     *
     * Returns:
     *     The saved ChecklistTemplate instance.
     */
    public function execute(array $data, ?ChecklistTemplate $template = null): ChecklistTemplate
    {
        return DB::transaction(function () use ($data, $template) {
            if (! $template) {
                $template = ChecklistTemplate::create([
                    'name' => $data['name'],
                    'instrument_type_id' => $data['instrument_type_id'],
                ]);
            } else {
                $template->update([
                    'name' => $data['name'] ?? $template->name,
                    'instrument_type_id' => $data['instrument_type_id'] ?? $template->instrument_type_id,
                ]);
                $template->items()->delete();
            }

            foreach ($data['items'] as $item) {
                $template->items()->create([
                    'step' => $item['step'],
                    'question_type' => $item['question_type'],
                    'order' => $item['order'],
                    'required_readings' => $item['required_readings'] ?? 1,
                    'nominal_value' => $item['nominal_value'] ?? null,
                    'criteria' => $item['criteria'] ?? null,
                    'reference_standard_type_id' => $item['reference_standard_type_id'] ?? null,
                ]);
            }

            return $template;
        });
    }
}
