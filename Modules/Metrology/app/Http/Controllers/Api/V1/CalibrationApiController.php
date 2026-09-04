<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Metrology\Actions\ApproveCalibrationAction;
use Modules\Metrology\Actions\CreateCalibrationAction;
use Modules\Metrology\Actions\RejectCalibrationAction;
use Modules\Metrology\DTOs\CalibrationSubmissionDTO;
use Modules\Metrology\Exceptions\MetrologyException;
use Modules\Metrology\Http\Requests\StoreCalibrationRequest;
use Modules\Metrology\Http\Resources\CalibrationApiResource;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;

/**
 * Manages instrument and standard calibration records.
 */
class CalibrationApiController extends Controller
{
    /**
     * Returns a paginated list of all calibrations.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Calibration::with(['calibratedItem', 'performedBy', 'approvedBy']);

        if ($request->filled('instrument_id')) {
            $query->where('calibrated_item_id', $request->input('instrument_id'))
                ->where('calibrated_item_type', Instrument::class);
        }

        if ($request->filled('calibrated_item_type')) {
            $type = $request->input('calibrated_item_type');
            if ($type === 'reference_standard') {
                $query->where('calibrated_item_type', ReferenceStandard::class);
            } elseif ($type === 'instrument') {
                $query->where('calibrated_item_type', Instrument::class);
            }
        }

        if ($request->filled('result')) {
            $query->where('result', $request->input('result'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 20);

        return CalibrationApiResource::collection(
            $query->latest('calibration_date')->paginate($perPage)
        );
    }

    /**
     * Records a new calibration result.
     */
    public function store(StoreCalibrationRequest $request, CreateCalibrationAction $action): JsonResponse
    {
        $user = $request->user();
        $throttleKey = 'signature.'.$user->id;

        // Permite 5 tentativas antes de bloquear
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => 'Assinatura bloqueada por segurança. Tente novamente em '.ceil($seconds / 60).' minuto(s).',
                'retry_after' => $seconds,
            ], 429);
        }

        if (! Hash::check($request->password, $user->password)) {
            $attempts = RateLimiter::attempts($throttleKey) + 1; // Incremento manual para o cálculo de tempo

            // O bloqueio só acontece DEPOIS do hit.
            // O tempo de expiração do hit define quanto tempo a próxima tentativa ficará bloqueada.
            $decaySeconds = match (true) {
                $attempts >= 8 => 1800, // 30 min
                $attempts >= 7 => 900,  // 15 min
                $attempts >= 6 => 300,  // 5 min
                $attempts >= 5 => 60,   // 1 min (no 5º erro)
                default => 60           // Registro silencioso
            };

            RateLimiter::hit($throttleKey, $decaySeconds);

            return response()->json([
                'message' => 'Senha de assinatura inválida.',
                'attempts_left' => 5 - RateLimiter::attempts($throttleKey),
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        try {
            $dto = CalibrationSubmissionDTO::fromArray($request->validated());
            $calibration = $action->execute($dto);

            return response()->json([
                'message' => 'Calibration recorded and signed successfully.',
                'data' => new CalibrationApiResource($calibration),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Approves a calibration record.
     */
    public function approve(string|int $id, Request $request, ApproveCalibrationAction $action): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();
        $throttleKey = 'signature.'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json(['message' => 'Bloqueio ativo. Aguarde '.ceil($seconds / 60).' min.'], 429);
        }

        if (! Hash::check($request->password, $user->password)) {
            $attempts = RateLimiter::attempts($throttleKey) + 1;
            $decay = match (true) {
                $attempts >= 6 => 300,
                $attempts >= 5 => 60,
                default => 60
            };
            RateLimiter::hit($throttleKey, $decay);

            return response()->json(['message' => 'Invalid password. Digital signature failed.'], 403);
        }

        RateLimiter::clear($throttleKey);

        $calibration = Calibration::findOrFail($id);
        try {
            $action->execute($calibration, $user);
        } catch (MetrologyException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Calibration approved and signed.',
            'data' => new CalibrationApiResource($calibration->load('approvedBy')),
        ]);
    }

    public function show(string|int $id): CalibrationApiResource
    {
        $calibration = Calibration::with(['calibratedItem', 'performedBy', 'approvedBy', 'checklist.items.referenceStandard'])->findOrFail($id);

        return new CalibrationApiResource($calibration);
    }

    public function reject(string|int $id, RejectCalibrationAction $action): JsonResponse
    {
        $calibration = Calibration::findOrFail($id);
        $action->execute($calibration);

        return response()->json(['message' => 'Calibration returned for correction.']);
    }
}
