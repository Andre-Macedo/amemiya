<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\System\Models\User;
use Modules\System\Services\TwoFactorAuthenticationService;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorApiController extends Controller
{
    public function __construct(
        protected TwoFactorAuthenticationService $twoFactorService
    ) {}

    public function setup(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return new JsonResponse([
                'message' => 'A autenticação em dois fatores já está configurada e ativada.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $secret = $this->twoFactorService->generateSecretKey();
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        $company = (string) config('app.name', 'Amemiya Metrologia');
        $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($company, $user->email, $secret);
        $qrCodeSvg = $this->twoFactorService->getQrCodeInline($company, $user->email, $secret);

        return new JsonResponse([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'qr_code_svg' => $qrCodeSvg,
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            return new JsonResponse([
                'message' => 'Nenhum processo de configuração 2FA foi iniciado previamente.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $isValid = $this->twoFactorService->verifyKey($user->two_factor_secret, (string) $request->code);

        if (! $isValid) {
            return new JsonResponse([
                'message' => 'Código de autenticação inválido. Verifique o código exibido no seu aplicativo autenticador.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->two_factor_confirmed_at = now();
        $user->save();

        return new JsonResponse([
            'message' => 'Autenticação em dois fatores ativada com sucesso.',
            'status' => 'enabled',
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check((string) $request->password, (string) $user->password)) {
            return new JsonResponse([
                'message' => 'Senha incorreta. A confirmação de senha é obrigatória para desativar o 2FA.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return new JsonResponse([
            'message' => 'Autenticação em dois fatores desativada com sucesso.',
            'status' => 'disabled',
        ]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return new JsonResponse([
                'message' => 'A autenticação em dois fatores não está ativada.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'recovery_codes' => $user->two_factor_recovery_codes ?? [],
        ]);
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return new JsonResponse([
                'message' => 'A autenticação em dois fatores não está ativada.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (! Hash::check((string) $request->password, (string) $user->password)) {
            return new JsonResponse([
                'message' => 'Senha incorreta.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $newCodes = $this->twoFactorService->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $newCodes;
        $user->save();

        return new JsonResponse([
            'recovery_codes' => $newCodes,
            'message' => 'Novos códigos de recuperação gerados com sucesso.',
        ]);
    }
}
