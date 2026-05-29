<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;

/**
 * API Controller for aggregating metrology-specific dashboard statistics.
 */
class DashboardApiController extends Controller
{
    /**
     * Calculates and returns KPIs and activity logs for the dashboard.
     *
     * Returns:
     *     A JSON response with performance indicators and upcoming tasks.
     */
    public function stats(): JsonResponse
    {
        $totalInstruments = Instrument::count();
        $activeInstruments = Instrument::where('status', 'active')->count();

        // Compliance Rate Calculation
        $complianceRate = $totalInstruments > 0
            ? round(($activeInstruments / $totalInstruments) * 100, 1)
            : 100;

        // Upcoming Calibration Due Dates (Next 30 days)
        $upcomingDue = Instrument::where('status', 'active')
            ->whereBetween('calibration_due', [now(), now()->addDays(30)])
            ->orderBy('calibration_due')
            ->limit(5)
            ->get(['id', 'name', 'serial_number', 'calibration_due', 'status']);

        // Latest Calibration Activity
        $recentCalibrations = Calibration::with('calibratedItem')
            ->latest('calibration_date')
            ->limit(5)
            ->get();

        return response()->json([
            'kpi' => [
                'total_instruments' => $totalInstruments,
                'active_count' => $activeInstruments,
                'overdue_count' => Instrument::where('status', 'expired')->count(),
                'in_calibration_count' => Instrument::where('status', 'in_calibration')->count(),
                'compliance_rate' => $complianceRate,
            ],
            'upcoming_due' => $upcomingDue->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'serial_number' => $i->serial_number,
                'calibration_due' => $i->calibration_due?->toDateString(),
                'status' => $i->status,
            ]),
            'recent_calibrations' => $recentCalibrations->map(fn ($cal) => [
                'id' => $cal->id,
                'item_name' => $cal->calibratedItem?->name ?? 'Unknown',
                'result' => $cal->result instanceof \UnitEnum ? $cal->result->value : $cal->result,
                'date' => $cal->calibration_date?->toDateString(),
                'certificate' => $cal->certificate_number,
            ]),
        ]);
    }
}
