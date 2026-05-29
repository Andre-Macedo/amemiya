<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Models\NonConformity;

class GlobalSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        $results = [];

        // 1. Instruments
        $instruments = Instrument::where('name', 'like', "%{$query}%")
            ->orWhere('serial_number', 'like', "%{$query}%")
            ->orWhere('stock_number', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'serial_number', 'status']);

        foreach ($instruments as $item) {
            $results[] = [
                'type' => 'instrument',
                'id' => $item->id,
                'title' => $item->name,
                'subtitle' => "SN: {$item->serial_number}",
                'url' => "/dashboard/metrology/instruments/{$item->id}",
                'icon' => 'Gauge'
            ];
        }

        // 2. Standards
        $standards = ReferenceStandard::where('name', 'like', "%{$query}%")
            ->orWhere('serial_number', 'like', "%{$query}%")
            ->limit(3)
            ->get(['id', 'name', 'serial_number']);

        foreach ($standards as $item) {
            $results[] = [
                'type' => 'standard',
                'id' => $item->id,
                'title' => $item->name,
                'subtitle' => "SN: {$item->serial_number}",
                'url' => "/dashboard/metrology/standards/{$item->id}",
                'icon' => 'Ruler'
            ];
        }

        // 3. Calibrations (Certificates)
        // Assuming certificate_number exists or searching by ID
        $calibrations = Calibration::where('id', 'like', "%{$query}%")
            // ->orWhere('certificate_number', 'like', "%{$query}%") // Uncomment if column exists
            ->limit(3)
            ->get(['id', 'calibration_date', 'result']);

        foreach ($calibrations as $item) {
            $results[] = [
                'type' => 'calibration',
                'id' => $item->id,
                'title' => "Calibration #{$item->id}",
                'subtitle' => $item->calibration_date->format('d/m/Y') . " - " . ucfirst($item->result->value ?? $item->result),
                'url' => "/dashboard/metrology/calibrations/{$item->id}",
                'icon' => 'FileText'
            ];
        }

        // 4. Non-Conformities
        $ncs = NonConformity::where('title', 'like', "%{$query}%")
            ->orWhere('id', 'like', "%{$query}%")
            ->limit(3)
            ->get(['id', 'title', 'status']);

        foreach ($ncs as $item) {
            $results[] = [
                'type' => 'nc',
                'id' => $item->id,
                'title' => "NC #{$item->id}: {$item->title}",
                'subtitle' => "Status: " . ucfirst($item->status),
                'url' => "/dashboard/metrology/non-conformities/{$item->id}",
                'icon' => 'AlertTriangle'
            ];
        }

        return response()->json($results);
    }
}
