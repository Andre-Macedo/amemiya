<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\Models\Setting;

class SettingsApiController extends Controller
{
    /**
     * Gets a list of specific global settings.
     */
    public function index(Request $request): JsonResponse
    {
        $keys = $request->input('keys', ['strict_competence_enforcement']);

        $settings = Setting::whereIn('key', (array) $keys)->pluck('value', 'key')->toArray();

        // Ensure defaults are returned if missing
        if (! isset($settings['strict_competence_enforcement'])) {
            $settings['strict_competence_enforcement'] = 'false';
        }

        return response()->json($settings);
    }

    /**
     * Updates multiple global settings at once.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::setValue($key, (string) $value);
        }

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
