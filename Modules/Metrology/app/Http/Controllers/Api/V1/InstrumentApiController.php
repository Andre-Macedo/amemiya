<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Metrology\Actions\ExportInstrumentAuditDossierAction;
use Modules\Metrology\Actions\GetInstrumentDriftAction;
use Modules\Metrology\Actions\PrintBatchInstrumentLabelsAction;
use Modules\Metrology\Actions\PrintInstrumentLabelAction;
use Modules\Metrology\Exports\InstrumentsExport;
use Modules\Metrology\Http\Requests\StoreInstrumentRequest;
use Modules\Metrology\Http\Requests\UpdateInstrumentRequest;
use Modules\Metrology\Http\Resources\InstrumentApiResource;
use Modules\Metrology\Imports\InstrumentImport;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Services\CalibrationIntervalService;

/**
 * API Controller for metrology instrument lifecycle management.
 */
class InstrumentApiController extends Controller
{
    /**
     * Lists instruments with filtering and pagination.
     *
     * Args:
     *     request: The request with filters (search, status).
     *
     * Returns:
     *     A paginated collection of serialized instruments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Instrument::with(['station', 'calibrations', 'openNonConformity']);

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('serial_number', 'like', "%{$searchTerm}%")
                    ->orWhere('stock_number', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 20);

        return InstrumentApiResource::collection($query->latest()->paginate($perPage));
    }

    /**
     * Stores a new instrument.
     *
     * Args:
     *     request: The validated instrument data.
     *
     * Returns:
     *     A serialized representation of the created instrument.
     */
    public function store(StoreInstrumentRequest $request): InstrumentApiResource
    {
        $instrument = Instrument::create($request->validated());

        return new InstrumentApiResource($instrument);
    }

    /**
     * Shows detailed info for a specific instrument.
     *
     * Args:
     *     instrument: The instrument model instance.
     *
     * Returns:
     *     A serialized instrument record.
     */
    public function show(Instrument $instrument): InstrumentApiResource
    {
        $instrument->load([
            'station',
            'instrumentType',
            'openNonConformity',
            'attachments',
            'calibrations' => fn ($q) => $q->latest('calibration_date'),
        ]);

        return new InstrumentApiResource($instrument);
    }

    /**
     * Updates an existing instrument.
     *
     * Args:
     *     request: The validated update data.
     *     instrument: The instrument to update.
     *
     * Returns:
     *     A serialized representation of the updated instrument.
     */
    public function update(UpdateInstrumentRequest $request, Instrument $instrument): InstrumentApiResource
    {
        $instrument->update($request->validated());

        return new InstrumentApiResource($instrument);
    }

    /**
     * Deletes an instrument.
     *
     * Args:
     *     instrument: The instrument to remove.
     *
     * Returns:
     *     A success message.
     */
    public function destroy(Instrument $instrument): JsonResponse
    {
        $instrument->delete();

        return response()->json(['message' => 'Instrument deleted successfully']);
    }

    /**
     * Generates a PDF label with QR Code for the instrument.
     *
     * Args:
     *     instrument: The target instrument.
     *     action: The PDF generation service.
     *
     * Returns:
     *     The PDF binary content.
     */
    public function label(Instrument $instrument, PrintInstrumentLabelAction $action)
    {
        $pdfContent = $action->execute($instrument);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="label-'.$instrument->serial_number.'.pdf"');
    }

    /**
     * Generates a PDF containing labels for multiple instruments.
     *
     * Args:
     *     request: The request containing an array of instrument IDs.
     *     action: The batch PDF generation service.
     *
     * Returns:
     *     The PDF binary content.
     */
    public function batchLabels(Request $request, PrintBatchInstrumentLabelsAction $action)
    {
        $request->validate([
            'ids' => ['required', 'string'], // Expected as comma-separated string from frontend GET request
        ]);

        $idsArray = explode(',', $request->input('ids'));
        $instruments = Instrument::whereIn('id', $idsArray)->get();

        $pdfContent = $action->execute($instruments);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="batch-labels.pdf"');
    }

    /**
     * Analyzes and returns drift/trend data.
     *
     * Args:
     *     request: The request with optional nominal value filter.
     *     instrument: The target instrument.
     *     action: The drift analysis service.
     *
     * Returns:
     *     A JSON response with chart data.
     */
    public function drift(Request $request, Instrument $instrument, GetInstrumentDriftAction $action): JsonResponse
    {
        $result = $action->execute($instrument, $request->input('nominal_value'));

        return response()->json($result);
    }

    /**
     * Provides calibration interval adjustment recommendations.
     *
     * Args:
     *     instrument: The instrument to analyze.
     *     service: The stability analysis service.
     *
     * Returns:
     *     A JSON response with the recommendation data.
     */
    public function recommendation(Instrument $instrument, CalibrationIntervalService $service): JsonResponse
    {
        $recommendation = $service->analyze($instrument);

        if (! $recommendation) {
            return response()->json(['message' => 'No recommendation available', 'data' => null]);
        }

        return response()->json(['data' => $recommendation]);
    }

    /**
     * Exports instruments to an Excel file based on filters.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        return (new InstrumentsExport($filters))
            ->download('instruments_inventory_'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Imports instruments from an Excel or CSV file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            Excel::import(new InstrumentImport, $request->file('file'));

            return response()->json(['message' => 'Instruments imported successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error during import: '.$e->getMessage()], 422);
        }
    }

    /**
     * Provides a consolidated chronological life cycle of the instrument.
     */
    /**
     * Generates a ZIP dossier containing all history, certificates and attachments for audit.
     */
    public function dossier(Instrument $instrument, ExportInstrumentAuditDossierAction $action)
    {
        $zipPath = $action->execute($instrument);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
