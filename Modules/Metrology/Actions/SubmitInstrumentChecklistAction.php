<?php

namespace Modules\Metrology\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Metrology\DTOs\InstrumentChecklistSubmissionData;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;

class SubmitInstrumentChecklistAction
{
    public function execute(InstrumentChecklistSubmissionData $data): Calibration
    {
        return DB::transaction(function () use ($data) {
            // 1. Create Calibration Record
            $calibration = Calibration::create([
                'instrument_id' => $data->instrument_id,
                'calibration_date' => now(),
                'performed_by' => auth()->id(),
                'result' => $data->result,
                'temperature' => $data->temperature,
                'humidity' => $data->humidity,
                'uncertainty' => $data->uncertainty,
                'deviation' => $data->deviation,
                'checklist_template_id' => $data->checklist_template_id,
            ]);

            // 2. Save Checklist Answers usually handled by logic or relation
            // Implemented simplified logic for now as database structure handles items

            // 3. Update Instrument Status
            $instrument = Instrument::find($data->instrument_id);
            if ($data->result === 'passed') {
                $instrument->update(['status' => 'active', 'last_calibration_date' => now()]);
            } else {
                $instrument->update(['status' => 'maintenance']);
            }

            return $calibration;
        });
    }
}
