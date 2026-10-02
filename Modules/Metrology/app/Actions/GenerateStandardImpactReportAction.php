<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Services\PdfSignerService;
use Modules\System\Models\Setting;
use Modules\System\Models\User;
use Throwable;

class GenerateStandardImpactReportAction
{
    public function __construct(
        protected PdfSignerService $signer
    ) {}

    /**
     * Constrói os dados analíticos estruturados do impacto metrológico.
     *
     * @return array<string, mixed>
     */
    public function buildReportData(
        ReferenceStandard $standard,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $reason = null
    ): array {
        $query = Calibration::query()
            ->with(['calibratedItem', 'performedBy', 'checklist.items'])
            ->whereHas('checklist.items', function (Builder $q) use ($standard): void {
                $q->where('reference_standard_id', $standard->id);
            })
            ->where('status', '!=', 'draft');

        if ($startDate) {
            $query->whereDate('calibration_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('calibration_date', '<=', $endDate);
        }

        $calibrations = $query->orderBy('calibration_date', 'desc')->get();

        $impactedCalibrations = [];
        $uniqueInstrumentIds = [];
        $criticalCount = 0;
        $moderateCount = 0;
        $lowCount = 0;

        foreach ($calibrations as $cal) {
            $item = $cal->calibratedItem;
            $instrumentName = 'Instrumento Desconhecido';
            $tag = '-';
            $serial = '-';
            $location = '-';
            $isCritical = false;
            $mpe = null;

            if ($item instanceof Instrument) {
                $uniqueInstrumentIds[$item->id] = true;
                $instrumentName = $item->name;
                $tag = $item->stock_number ?? '-';
                $serial = $item->serial_number ?? '-';
                $location = $item->station?->name ?? $item->location ?? '-';
                $isCritical = $item->isCritical();
                $mpe = $item->getMaximumPermissibleError();
            }

            $uncertainty = $cal->uncertainty !== null ? (float) $cal->uncertainty : null;
            $tur = null;

            if ($mpe !== null && $mpe > 0 && $uncertainty !== null && $uncertainty > 0) {
                $tur = round($mpe / $uncertainty, 2);
            }

            // Classificação de Risco ISO 17025 §7.10
            $isMarginalApproval = in_array($cal->result, [
                CalibrationResult::ApprovedWithRestrictions,
                'approved_with_restrictions',
            ], true);

            if ($isCritical || $isMarginalApproval || ($tur !== null && $tur < 3.0)) {
                $risk = 'CRITICAL';
                $action = 'Recall Imediato / Quarentena';
                $criticalCount++;
            } elseif ($tur !== null && $tur < 4.0) {
                $risk = 'MODERATE';
                $action = 'Recalibração Preventiva';
                $moderateCount++;
            } else {
                $risk = 'LOW';
                $action = 'Avaliação Documental';
                $lowCount++;
            }

            $impactedCalibrations[] = [
                'calibration_id' => $cal->id,
                'cert_code' => $cal->certificate_code ?? $cal->certificate_number ?? ('CERT-' . substr((string) $cal->id, -6)),
                'date' => $cal->calibration_date ? $cal->calibration_date->format('d/m/Y') : '-',
                'instrument_name' => $instrumentName,
                'tag' => $tag,
                'serial' => $serial,
                'location' => $location,
                'mpe' => $mpe,
                'uncertainty' => $uncertainty,
                'tur' => $tur,
                'risk' => $risk,
                'action' => $action,
            ];
        }

        $stats = [
            'total_calibrations' => count($impactedCalibrations),
            'unique_instruments' => count($uniqueInstrumentIds),
            'critical_count' => $criticalCount,
            'moderate_count' => $moderateCount,
            'low_count' => $lowCount,
        ];

        $reportCode = 'RTI-' . ($standard->stock_number ?? 'STD' . $standard->id) . '-' . now()->format('Ymd-His');

        $identity = [
            'lab_name' => Setting::getValue('lab_name', config('app.name', 'Sistema de Metrologia Lean Tech')),
            'lab_address' => Setting::getValue('lab_address', 'Rastreabilidade e Qualidade Assegurada'),
            'lab_contact' => Setting::getValue('lab_contact', ''),
            'accent_color' => Setting::getValue('lab_accent_color', '#dc2626'),
        ];

        // Hash forense determinístico do payload dos dados do laudo
        $fingerprintPayload = [
            'report_code' => $reportCode,
            'standard_id' => $standard->id,
            'standard_serial' => $standard->serial_number,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'stats' => $stats,
            'calibrations_hashes' => array_map(fn($c) => $c['cert_code'] . ':' . $c['risk'], $impactedCalibrations),
            'timestamp' => now()->toIso8601String(),
        ];
        $documentHash = hash('sha256', (string) json_encode($fingerprintPayload));

        return [
            'reportCode' => $reportCode,
            'standard' => $standard,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reason' => $reason,
            'stats' => $stats,
            'impactedCalibrations' => $impactedCalibrations,
            'identity' => $identity,
            'documentHash' => $documentHash,
            'technicalManager' => Setting::getValue('technical_manager_name', 'Responsável Técnico Metrológico'),
            'qualityManager' => Setting::getValue('quality_manager_name', 'Gerência da Qualidade'),
        ];
    }

    /**
     * Gera o arquivo PDF binário do laudo de impacto com assinatura opcional.
     */
    public function execute(
        ReferenceStandard $standard,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $reason = null
    ): string {
        $reportData = $this->buildReportData($standard, $startDate, $endDate, $reason);

        $pdf = Pdf::loadView('metrology::pdf.standard_impact_report', $reportData);
        $pdfContent = $pdf->output();

        // Opcional: Assinatura digital com chave privada do laboratório
        $certPath = config('metrology.certificate_path');
        $certPass = config('metrology.certificate_password');

        if ($certPath && file_exists($certPath)) {
            try {
                $pdfContent = $this->signer->sign($pdfContent, $certPath, $certPass);
            } catch (Throwable $exception) {
                logger()->error("Standard Impact Report PDF Signing Failed: {$exception->getMessage()}");
            }
        }

        return $pdfContent;
    }
}
