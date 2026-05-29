<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\Models\Setting;
use Modules\System\Models\User;

class UserCompetenceApiController extends Controller
{
    /**
     * Get the competence matrix for a specific user.
     */
    public function index(User $user): JsonResponse
    {
        $competences = $user->competences()->get()->map(function ($type) {
            return [
                'instrument_type_id' => $type->id,
                'instrument_type_name' => $type->name,
                'valid_until' => $type->pivot->valid_until,
                'is_valid' => $type->pivot->valid_until === null || Carbon::parse($type->pivot->valid_until)->isFuture(),
            ];
        });

        return response()->json($competences);
    }

    /**
     * Updates the competence matrix for a user.
     */
    public function sync(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'competences' => ['required', 'array'],
            'competences.*.instrument_type_id' => ['required', 'exists:instrument_types,id'],
            'competences.*.valid_until' => ['nullable', 'date'],
        ]);

        $syncData = [];
        foreach ($validated['competences'] as $item) {
            $syncData[$item['instrument_type_id']] = ['valid_until' => $item['valid_until']];
        }

        $user->competences()->sync($syncData);

        return response()->json(['message' => 'Competence matrix updated successfully.']);
    }

    /**
     * Check if the currently authenticated user has competence for a specific instrument type.
     * Useful for early UI warnings.
     */
    public function check(Request $request, int $instrumentTypeId): JsonResponse
    {
        $user = $request->user();

        if (! method_exists($user, 'hasValidCompetenceFor')) {
            return response()->json(['has_competence' => true, 'strict' => false]);
        }

        $hasCompetence = $user->hasValidCompetenceFor($instrumentTypeId);
        $isStrict = Setting::getValue('strict_competence_enforcement', 'false') === 'true';

        return response()->json([
            'has_competence' => $hasCompetence,
            'is_strict_enforced' => $isStrict,
            'can_proceed' => $hasCompetence || ! $isStrict,
        ]);
    }
}
