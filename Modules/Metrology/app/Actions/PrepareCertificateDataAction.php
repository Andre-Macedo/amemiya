<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ChecklistItem;

class PrepareCertificateDataAction
{
    public function execute(Calibration $calibration): array
    {
        // Ensure relationships are loaded
        $calibration->load(['checklist.items.referenceStandard', 'calibratedItem', 'performedBy']);

        $results = [];
        $standards = collect();

        if ($calibration->checklist) {
            foreach ($calibration->checklist->items as $item) {
                // Collect Unique Standards
                if ($item->referenceStandard) {
                    $standards->push($item->referenceStandard);
                }

                // Process Numeric Items (Readings)
                if ($item->question_type === 'numeric' && !empty($item->readings)) {
                    
                    // Logic to extract relevant data for the table
                    // Assuming readings is an array of ['value' => 10.01]
                    $readings = collect($item->readings)->pluck('value')->filter()->map(fn($v)=>(float)$v);
                    
                    if ($readings->isEmpty()) continue;

                    $avg = $readings->avg();
                    $nominal = (float) $item->nominal_value ?? (float) filter_var($item->step, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $error = $avg - $nominal;
                    
                    $results[] = [
                        'step' => $item->step,
                        'nominal' => $nominal,
                        'readings' => $readings->toArray(),
                        'average' => $avg,
                        'error' => $error,
                        'uncertainty' => $item->uncertainty ?? $calibration->uncertainty, // Fallback to global if item specific missing
                        'k_factor' => 2.00, // Standard K=2
                        'result' => $item->result ?? ($calibration->result === 'approved' ? 'Approved' : 'Rejected'),
                    ];
                }
            }
        }

        return [
            'results' => $results,
            'standards' => $standards->unique('id')->values()->all(),
        ];
    }
}
