<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Modules\System\Models\Setting;
use Throwable;

class CertificateApiController extends Controller
{
    /**
     * Get digital certificate status and metadata for current tenant.
     */
    public function show(): JsonResponse
    {
        $path = Setting::getValue('lab_certificate_path');
        $validTo = Setting::getValue('lab_certificate_valid_to');
        $validFrom = Setting::getValue('lab_certificate_valid_from');
        $commonName = Setting::getValue('lab_certificate_common_name');
        $issuer = Setting::getValue('lab_certificate_issuer');
        $serial = Setting::getValue('lab_certificate_serial');

        if (! $path) {
            return response()->json([
                'configured' => false,
                'is_valid' => false,
                'common_name' => null,
                'issuer' => null,
                'valid_from' => null,
                'valid_to' => null,
                'days_remaining' => null,
                'serial_number' => null,
            ]);
        }

        $now = Carbon::now();
        $expiresAt = $validTo ? Carbon::parse($validTo) : null;
        $isValid = $expiresAt ? $expiresAt->isFuture() : false;
        $daysRemaining = ($expiresAt && $isValid) ? (int) $now->diffInDays($expiresAt, false) : 0;

        return response()->json([
            'configured' => true,
            'is_valid' => $isValid,
            'common_name' => $commonName,
            'issuer' => $issuer,
            'valid_from' => $validFrom ? Carbon::parse($validFrom)->toIso8601String() : null,
            'valid_to' => $validTo ? Carbon::parse($validTo)->toIso8601String() : null,
            'days_remaining' => $daysRemaining,
            'serial_number' => $serial,
        ]);
    }

    /**
     * Upload and validate a new digital certificate (.pfx / .p12 / .pem).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'certificate' => ['required', 'file', 'max:10240'], // 10MB max
            'password' => ['required', 'string'],
        ]);

        $file = $request->file('certificate');
        $content = file_get_contents($file->getRealPath());
        $password = $request->input('password');

        $certs = [];
        $isPkcs12 = @openssl_pkcs12_read($content, $certs, $password);
        $x509Data = null;
        $pemContent = '';

        if ($isPkcs12 && isset($certs['cert'], $certs['pkey'])) {
            $x509Data = @openssl_x509_parse($certs['cert']);
            $pemContent = $certs['cert']."\n".$certs['pkey'];
            if (! empty($certs['extracerts'])) {
                if (is_array($certs['extracerts'])) {
                    $pemContent .= "\n".implode("\n", $certs['extracerts']);
                } else {
                    $pemContent .= "\n".$certs['extracerts'];
                }
            }
        } else {
            // Tentativa de leitura em formato PEM
            $x509Resource = @openssl_x509_read($content);
            $privateKeyResource = $x509Resource ? @openssl_pkey_get_private($content, $password) : false;

            if ($x509Resource !== false && $privateKeyResource !== false) {
                $x509Data = @openssl_x509_parse($x509Resource);
                $pemContent = $content;
            }
        }

        if (! $x509Data || empty($pemContent)) {
            return response()->json([
                'message' => 'A senha informada está incorreta ou o arquivo não é um certificado válido (.pfx, .p12 ou .pem).',
            ], 422);
        }

        $validToTime = $x509Data['validTo_time_t'] ?? 0;
        $validFromTime = $x509Data['validFrom_time_t'] ?? 0;

        if (time() > $validToTime) {
            $formattedDate = date('d/m/Y H:i', $validToTime);

            return response()->json([
                'message' => "O certificado digital informado está expirado desde {$formattedDate}. Não é permitido cadastrar certificados vencidos.",
            ], 422);
        }

        // Determina diretório seguro do tenant
        $tenantId = tenancy()->initialized ? (string) tenancy()->tenant->id : 'default';
        $relativeDir = "tenants/{$tenantId}/certificates";
        $pemRelativePath = "{$relativeDir}/certificate.pem";

        // Salva com permissões privadas
        Storage::disk('local')->put($pemRelativePath, $pemContent);

        // Extrai metadados amigáveis
        $subjectCn = $x509Data['subject']['CN'] ?? ($x509Data['subject']['commonName'] ?? 'Laboratório Metrológico');
        $issuerCn = $x509Data['issuer']['CN'] ?? ($x509Data['issuer']['O'] ?? 'Autoridade Certificadora');
        $serial = (string) ($x509Data['serialNumberHex'] ?? ($x509Data['serialNumber'] ?? ''));

        // Persiste as configurações protegidas por tenant
        Setting::setValue('lab_certificate_path', $pemRelativePath);
        Setting::setValue('lab_certificate_password', Crypt::encryptString($password));
        Setting::setValue('lab_certificate_common_name', $subjectCn);
        Setting::setValue('lab_certificate_issuer', $issuerCn);
        Setting::setValue('lab_certificate_valid_from', date('Y-m-d H:i:s', $validFromTime));
        Setting::setValue('lab_certificate_valid_to', date('Y-m-d H:i:s', $validToTime));
        Setting::setValue('lab_certificate_serial', $serial);

        $now = Carbon::now();
        $expiresAt = Carbon::createFromTimestamp($validToTime);
        $daysRemaining = (int) $now->diffInDays($expiresAt, false);

        return response()->json([
            'message' => 'Certificado digital A1 configurado com sucesso.',
            'certificate' => [
                'configured' => true,
                'is_valid' => true,
                'common_name' => $subjectCn,
                'issuer' => $issuerCn,
                'valid_from' => Carbon::createFromTimestamp($validFromTime)->toIso8601String(),
                'valid_to' => $expiresAt->toIso8601String(),
                'days_remaining' => $daysRemaining,
                'serial_number' => $serial,
            ],
        ], 200);
    }

    /**
     * Remove the current digital certificate and associated keys.
     */
    public function destroy(): JsonResponse
    {
        $path = Setting::getValue('lab_certificate_path');
        if ($path && Storage::disk('local')->exists($path)) {
            try {
                Storage::disk('local')->delete($path);
            } catch (Throwable) {
                // Silently proceed
            }
        }

        Setting::whereIn('key', [
            'lab_certificate_path',
            'lab_certificate_password',
            'lab_certificate_common_name',
            'lab_certificate_issuer',
            'lab_certificate_valid_from',
            'lab_certificate_valid_to',
            'lab_certificate_serial',
        ])->delete();

        return response()->json([
            'message' => 'Certificado digital removido com sucesso.',
        ]);
    }
}
