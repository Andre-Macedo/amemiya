<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Illuminate\Support\Collection;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\NonConformity;
use Modules\Metrology\Models\WorkOrder;

class GetInstrumentLifeCycleAction
{
    /**
     * Consolidates all lifecycle events for an instrument into a chronological timeline.
     */
    public function execute(Instrument $instrument): Collection
    {
        $events = collect();

        // 1. Calibration Events
        $instrument->calibrations()->get()->each(function (Calibration $cal) use ($events) {
            $events->push([
                'type' => 'calibration',
                'title' => 'Calibration Performed',
                'description' => "Certificate #{$cal->certificate_number}. Result: ".strtoupper($cal->result->value),
                'date' => $cal->calibration_date->toIso8601String(),
                'status' => $cal->result->value, // approved, rejected, etc
                'link' => "/dashboard/metrology/calibrations/{$cal->id}",
                'icon' => 'gauge',
            ]);
        });

        // 2. Work Order Events (Check-ins)
        WorkOrder::where('item_type', Instrument::class)
            ->where('item_id', $instrument->id)
            ->get()
            ->each(function (WorkOrder $wo) use ($events) {
                $events->push([
                    'type' => 'work_order',
                    'title' => 'Instrument Received',
                    'description' => "Received for service via WO #{$wo->number}. Notes: {$wo->visual_inspection_notes}",
                    'date' => $wo->created_at->toIso8601String(),
                    'status' => $wo->status,
                    'link' => "/dashboard/metrology/work-orders/{$wo->id}",
                    'icon' => 'package',
                ]);
            });

        // 3. Non-Conformity Events
        NonConformity::where('item_type', Instrument::class)
            ->where('item_id', $instrument->id)
            ->get()
            ->each(function (NonConformity $nc) use ($events) {
                $events->push([
                    'type' => 'non_conformity',
                    'title' => 'Non-Conformity Opened',
                    'description' => "NC #{$nc->id}: {$nc->title}. Priority: ".strtoupper($nc->priority),
                    'date' => $nc->created_at->toIso8601String(),
                    'status' => $nc->status, // open, resolved, closed
                    'link' => "/dashboard/metrology/non-conformities/{$nc->id}",
                    'icon' => 'alert-triangle',
                ]);

                if ($nc->closed_at) {
                    $events->push([
                        'type' => 'non_conformity_closed',
                        'title' => 'Non-Conformity Closed',
                        'description' => "Corrective action implemented and verified for NC #{$nc->id}.",
                        'date' => $nc->closed_at->toIso8601String(),
                        'status' => 'closed',
                        'link' => "/dashboard/metrology/non-conformities/{$nc->id}",
                        'icon' => 'check-circle',
                    ]);
                }
            });

        // 4. Creation Event
        $events->push([
            'type' => 'creation',
            'title' => 'Instrument Registered',
            'description' => 'Initial registration of the asset in the system.',
            'date' => $instrument->created_at->toIso8601String(),
            'status' => 'active',
            'icon' => 'plus-circle',
        ]);

        return $events->sortByDesc('date')->values();
    }
}
