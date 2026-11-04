<?php

declare(strict_types=1);

namespace Modules\System\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\System\Models\User;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorAuthenticationService
{
    public function __construct(
        protected Google2FA $google2fa = new Google2FA
    ) {}

    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return Collection::times($count, function (): string {
            return Str::random(10).'-'.Str::random(10);
        })->all();
    }

    public function getQrCodeUrl(string $company, string $holder, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($company, $holder, $secret);
    }

    public function getQrCodeInline(string $company, string $holder, string $secret, int $size = 200): string
    {
        return $this->google2fa->getQRCodeInline($company, $holder, $secret, $size);
    }

    public function verifyKey(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code);
    }

    public function verifyCodeOrRecovery(User $user, string $code): bool
    {
        if (empty($user->two_factor_secret)) {
            return false;
        }

        if ($this->verifyKey($user->two_factor_secret, $code)) {
            return true;
        }

        $recoveryCodes = $user->two_factor_recovery_codes ?? [];

        if (! is_array($recoveryCodes)) {
            return false;
        }

        $foundIndex = array_search($code, $recoveryCodes, true);

        if ($foundIndex === false) {
            return false;
        }

        unset($recoveryCodes[$foundIndex]);
        $user->two_factor_recovery_codes = array_values($recoveryCodes);
        $user->save();

        return true;
    }
}
