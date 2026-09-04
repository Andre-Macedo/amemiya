<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Illuminate\Support\Facades\Auth;
use Modules\Metrology\Exceptions\MetrologyException;
use Modules\Metrology\Models\Calibration;
use Modules\System\Models\User;

/**
 * Handles the approval and publishing workflow for a calibration.
 */
class ApproveCalibrationAction
{
    /**
     * Approves a calibration and marks it as published.
     *
     * @throws \RuntimeException if the calibration is already published.
     * @throws MetrologyException if segregation of duties is violated.
     */
    public function execute(Calibration $calibration, ?User $approver = null): Calibration
    {
        if ($calibration->status === 'published') {
            throw new \RuntimeException('This calibration is already approved and published.');
        }

        $approverUser = $approver ?? Auth::user();
        $approverId = $approverUser?->id ?? Auth::id();

        // Segregação de Funções (ISO/IEC 17025 / 21 CFR Part 11):
        // O executor da calibração não pode aprovar a si mesmo,
        // salvo permissão explícita para laboratório unipessoal.
        if ($approverId && $calibration->performed_by_id && (string) $calibration->performed_by_id === (string) $approverId) {
            $canSelfApprove = $approverUser && method_exists($approverUser, 'can') && $approverUser->can('metrology.calibrations.self-approve');
            if (! $canSelfApprove) {
                throw new MetrologyException('Segregação de funções violada: o técnico responsável pela calibração não pode aprovar seu próprio certificado.');
            }
        }

        $calibration->update([
            'status' => 'published',
            'approved_by_id' => $approverId,
            'approved_at' => now(),
        ]);

        return $calibration;
    }
}
