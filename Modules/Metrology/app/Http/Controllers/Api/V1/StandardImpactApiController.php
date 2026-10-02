<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Metrology\Actions\GenerateStandardImpactReportAction;
use Modules\Metrology\Models\ReferenceStandard;

class StandardImpactApiController extends Controller
{
    public function __construct(
        protected GenerateStandardImpactReportAction $reportAction
    ) {}

    /**
     * Retorna a análise de impacto metrológico reversa (Padrão -> Instrumentos Calibrados).
     */
    public function index(Request $request, ReferenceStandard $standard): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'reason' => 'nullable|string|max:500',
        ]);

        $reportData = $this->reportAction->buildReportData(
            $standard,
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('reason')
        );

        return response()->json([
            'data' => $reportData['impactedCalibrations'],
            'stats' => $reportData['stats'],
            'meta' => [
                'total' => $reportData['stats']['total_calibrations'],
            ],
            'report_code' => $reportData['reportCode'],
            'document_hash' => $reportData['documentHash'],
        ]);
    }

    /**
     * Gera e realiza download do Laudo Formal de Impacto e Recall em PDF (ISO/IEC 17025 §7.10).
     */
    public function pdf(Request $request, ReferenceStandard $standard): Response
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'reason' => 'nullable|string|max:500',
        ]);

        $pdfContent = $this->reportAction->execute(
            $standard,
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('reason')
        );

        $fileName = 'Laudo_Impacto_' . ($standard->stock_number ?? 'STD-' . $standard->id) . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
