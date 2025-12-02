<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\Metrology\Models\Instrument;

class DashboardApiController extends Controller
{
    public function stats()
    {
        return response()->json([
            'active_count' => Instrument::where('status', 'active')->count(),
            'overdue_count' => Instrument::where('status', 'expired')->count(),
            'calibration_count' => Instrument::where('status', 'in_calibration')->count(),
        ]);
    }
}
