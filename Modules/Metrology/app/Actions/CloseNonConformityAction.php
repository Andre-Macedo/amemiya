<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Illuminate\Support\Facades\Auth;
use Modules\Metrology\Models\NonConformity;

/**
 * Handles the business logic for closing a non-conformity report.
 */
class CloseNonConformityAction
{
    /**
     * Closes an NC report after validating required analysis.
     *
     * Args:
     *     nc: The non-conformity instance to close.
     *
     * Returns:
     *     The updated NonConformity instance.
     *
     * Throws:
     *     \RuntimeException if required analysis fields are missing.
     */
    public function execute(NonConformity $nc): NonConformity
    {
        // Business Validation: Cannot close without analysis and actions
        if (empty($nc->root_cause_analysis) || empty($nc->corrective_action)) {
            throw new \RuntimeException('Cannot close NC without Root Cause Analysis and Corrective Action.');
        }

        $nc->update([
            'status' => 'closed',
            'closed_by' => Auth::id(),
            'closed_at' => now(),
        ]);

        return $nc;
    }
}
