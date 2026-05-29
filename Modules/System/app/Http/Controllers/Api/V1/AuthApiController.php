<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Modules\System\Models\User;

/**
 * Handles user authentication via Sanctum tokens with Progressive Rate Limiting.
 */
class AuthApiController extends Controller
{
    /**
     * Authenticates a user and returns a bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = 'login.'.$request->ip().'.'.$request->email;

        // Permite 5 tentativas antes de bloquear
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            return response()->json([
                'message' => "Muitas tentativas de login. Sua conta está temporariamente bloqueada por mais {$minutes} minuto(s).",
                'status' => 'locked',
                'retry_after_seconds' => $seconds,
            ], 429);
        }

        if (! Auth::attempt($credentials)) {
            $attempts = RateLimiter::attempts($throttleKey) + 1;

            // Lógica Progressiva Combinada (Bloqueio após o hit atual atingir o limite)
            $decaySeconds = match (true) {
                $attempts >= 10 => 1800, // 30 min (punição severa)
                $attempts >= 7 => 300,  // 5 min
                $attempts >= 5 => 60,   // 1 min (bloqueia na 5ª errada)
                default => 60
            };

            RateLimiter::hit($throttleKey, $decaySeconds);

            return response()->json([
                'message' => 'Credenciais inválidas. Verifique seus dados e tente novamente.',
                'attempts_left' => 5 - RateLimiter::attempts($throttleKey),
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = User::where('email', $request['email'])->firstOrFail();

        return $this->issueTokenResponse($user);
    }

    /**
     * Validates an impersonation token and returns a bearer token.
     */
    public function impersonate(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $impersonationData = tenancy()->impersonate()->load($request->token);

        if (! $impersonationData) {
            return response()->json([
                'message' => 'Token de acesso expirado ou inválido.',
            ], 403);
        }

        /** @var User $user */
        $user = User::findOrFail($impersonationData['user_id']);

        return $this->issueTokenResponse($user);
    }

    /**
     * Common method to issue a token response.
     */
    protected function issueTokenResponse(User $user): JsonResponse
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        $tenant = $user->tenant;
        $domain = $tenant ? $tenant->domains()->first()?->domain : null;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'tenant_id' => $user->tenant_id,
                'tenant_slug' => $tenant?->slug,
                'redirect_domain' => $domain,
                'terms_accepted_at' => $user->terms_accepted_at,
            ],
        ]);
    }

    /**
     * Revokes the user's current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Tokens Revoked',
        ]);
    }
}
