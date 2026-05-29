<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Modules\System\Models\User;

/**
 * Manages the authenticated user's profile and settings.
 */
class ProfileApiController extends Controller
{
    /**
     * Returns the current authenticated user's profile data.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * Updates the authenticated user's profile information.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Updates the authenticated user's password.
     */
    public function password(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }

    /**
     * Uploads and updates the user's digital signature image.
     */
    public function updateSignature(Request $request): JsonResponse
    {
        $request->validate([
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->signature_image_path) {
            Storage::disk('public')->delete($user->signature_image_path);
        }

        $path = $request->file('signature')->store('signatures', 'public');

        $user->update([
            'signature_image_path' => $path,
        ]);

        return response()->json([
            'message' => 'Signature updated successfully',
            'signature_url' => asset('storage/'.$path),
            'user' => $user,
        ]);
    }

    /**
     * Removes the user's digital signature image.
     */
    public function deleteSignature(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->signature_image_path) {
            Storage::disk('public')->delete($user->signature_image_path);
            $user->update([
                'signature_image_path' => null,
            ]);
        }

        return response()->json([
            'message' => 'Signature removed successfully',
            'user' => $user,
        ]);
    }

    /**
     * Records the user's acceptance of legal terms.
     */
    public function acceptLegal(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->update([
            'terms_accepted_at' => now(),
            'privacy_policy_accepted_at' => now(),
            'acceptance_ip' => $request->ip(),
        ]);

        return response()->json(['message' => 'Termos aceitos com sucesso.']);
    }
}
