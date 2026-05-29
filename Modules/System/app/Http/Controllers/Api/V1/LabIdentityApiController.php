<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\System\Models\Setting;

class LabIdentityApiController extends Controller
{
    /**
     * Get laboratory identity settings for white-labeling.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'lab_name' => Setting::getValue('lab_name', config('app.name')),
            'lab_address' => Setting::getValue('lab_address', ''),
            'lab_contact' => Setting::getValue('lab_contact', ''),
            'lab_logo_url' => Setting::getValue('lab_logo_path') ? Storage::disk('public')->url(Setting::getValue('lab_logo_path')) : null,
            'certificate_footer' => Setting::getValue('certificate_footer', 'Digital signature compliant with FDA 21 CFR Part 11.'),
            'accent_color' => Setting::getValue('lab_accent_color', '#3b82f6'),
        ]);
    }

    /**
     * Update laboratory identity settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lab_name' => ['nullable', 'string', 'max:255'],
            'lab_address' => ['nullable', 'string'],
            'lab_contact' => ['nullable', 'string'],
            'certificate_footer' => ['nullable', 'string'],
            'accent_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'logo' => ['nullable', 'image', 'max:2048'], // 2MB max
        ]);

        foreach ($request->only(['lab_name', 'lab_address', 'lab_contact', 'certificate_footer', 'lab_accent_color']) as $key => $value) {
            if ($value !== null) {
                Setting::setValue($key, $value);
            }
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'public');
            Setting::setValue('lab_logo_path', $path);
        }

        return response()->json(['message' => 'Laboratory identity updated successfully.']);
    }
}
