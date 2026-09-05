<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Metrology\Actions\GenerateCertificatePdfAction;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\LabClient;
use Modules\System\Models\Setting;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicClientPortalController extends Controller
{
    /**
     * Autentica o cliente final no portal white-label usando CNPJ e Token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'cnpj' => ['required', 'string'],
            'token' => ['required', 'string'],
        ]);

        $client = LabClient::where('cnpj', $request->cnpj)
            ->where('access_token', $request->token)
            ->first();

        if (! $client) {
            return response()->json(['message' => 'Credenciais de acesso inválidas. Verifique o CNPJ e o Código de Acesso.'], 401);
        }

        // Emite token genuíno do Laravel Sanctum para a sessão do portal
        $authToken = $client->createToken('portal_access', ['portal:read', 'portal:download'])->plainTextToken;

        $branding = [
            'lab_name' => Setting::getValue('lab_name', $client->tenant?->name ?? config('app.name')),
            'lab_logo_url' => Setting::getValue('lab_logo_path') ? Storage::disk('public')->url(Setting::getValue('lab_logo_path')) : null,
            'accent_color' => Setting::getValue('lab_accent_color', '#3b82f6'),
        ];

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'cnpj' => $client->cnpj,
                'email' => $client->email,
                'tenant_id' => $client->tenant_id,
            ],
            'branding' => $branding,
            'auth_token' => $authToken,
        ]);
    }

    /**
     * Retorna o perfil do cliente autenticado e os dados de branding.
     */
    public function me(Request $request): JsonResponse
    {
        $client = $this->getAuthenticatedClient($request);

        $branding = [
            'lab_name' => Setting::getValue('lab_name', $client->tenant?->name ?? config('app.name')),
            'lab_logo_url' => Setting::getValue('lab_logo_path') ? Storage::disk('public')->url(Setting::getValue('lab_logo_path')) : null,
            'accent_color' => Setting::getValue('lab_accent_color', '#3b82f6'),
        ];

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'cnpj' => $client->cnpj,
                'email' => $client->email,
                'tenant_id' => $client->tenant_id,
            ],
            'branding' => $branding,
        ]);
    }

    /**
     * Lista os certificados disponíveis para o cliente logado no portal.
     */
    public function certificates(Request $request, ?string $clientId = null): JsonResponse
    {
        $client = $this->getAuthenticatedClient($request, $clientId);

        $query = Calibration::where('lab_client_id', $client->id)
            ->where('status', 'published')
            ->with(['calibratedItem', 'checklist.items'])
            ->latest('calibration_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_code', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHasMorph('calibratedItem', [Instrument::class], function ($iq) use ($search) {
                        $iq->where('name', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    });
            });
        }

        $calibrations = $query->get();

        return response()->json($calibrations->map(fn (Calibration $cal) => [
            'id' => $cal->id,
            'certificate_code' => $cal->certificate_code,
            'instrument' => $cal->calibratedItem?->name ?? 'Instrumento',
            'serial_number' => $cal->calibratedItem?->serial_number ?? 'S/N',
            'date' => $cal->calibration_date?->format('d/m/Y') ?? $cal->created_at->format('d/m/Y'),
            'next_due_date' => $cal->next_due_date?->format('d/m/Y'),
            'result' => $cal->result?->getLabel() ?? 'Concluído',
            'result_value' => $cal->result?->value ?? 'approved',
            'download_url' => url("/api/v1/public/portal/certificates/{$cal->id}/download"),
        ]));
    }

    /**
     * Faz o download direto do PDF do certificado pertencente ao cliente autenticado.
     */
    public function downloadCertificate(Request $request, string $id, GenerateCertificatePdfAction $generator)
    {
        $client = $this->getAuthenticatedClient($request);

        $calibration = Calibration::where('id', $id)
            ->where('lab_client_id', $client->id)
            ->firstOrFail();

        $pdfContent = $generator->execute($calibration);
        $filename = "Certificado_{$calibration->certificate_code}.pdf";

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Faz o download em lote de múltiplos certificados em um arquivo compactado ZIP.
     */
    public function downloadZip(Request $request, GenerateCertificatePdfAction $generator): BinaryFileResponse|JsonResponse
    {
        $client = $this->getAuthenticatedClient($request);

        $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['string'],
        ]);

        $query = Calibration::where('lab_client_id', $client->id)
            ->where('status', 'published')
            ->with(['calibratedItem', 'checklist.items', 'performedBy']);

        if ($request->filled('ids')) {
            $query->whereIn('id', $request->input('ids'));
        }

        $calibrations = $query->get();

        if ($calibrations->isEmpty()) {
            return response()->json(['message' => 'Nenhum certificado selecionado para download.'], 404);
        }

        $zipFilename = 'Certificados_'.Str::slug($client->name).'_'.now()->format('Ymd_His').'.zip';
        $tempDir = storage_path('app/temp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $tempZipPath = $tempDir.DIRECTORY_SEPARATOR.$zipFilename;

        $zip = new \ZipArchive();
        if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'Não foi possível criar o pacote ZIP.'], 500);
        }

        foreach ($calibrations as $calibration) {
            $pdfContent = $generator->execute($calibration);
            $code = $calibration->certificate_code ?: $calibration->id;
            $instrumentName = Str::slug($calibration->calibratedItem?->name ?? 'Instrumento');
            $certFilename = "{$code}_{$instrumentName}.pdf";
            $zip->addFromString($certFilename, $pdfContent);
        }

        $zip->close();

        return response()->download($tempZipPath, $zipFilename)->deleteFileAfterSend(true);
    }

    /**
     * Lista todos os instrumentos associados ao cliente com indicadores de validade.
     */
    public function instruments(Request $request): JsonResponse
    {
        $client = $this->getAuthenticatedClient($request);

        $instruments = Instrument::where('lab_client_id', $client->id)
            ->orWhereHas('calibrations', fn ($q) => $q->where('lab_client_id', $client->id))
            ->with(['instrumentType', 'calibrations' => fn ($q) => $q->where('lab_client_id', $client->id)->latest('calibration_date')])
            ->get();

        return response()->json($instruments->map(function (Instrument $inst) use ($client) {
            $now = now();
            $due = $inst->calibration_due;

            $status = 'valid';
            if (! $due) {
                $status = 'unknown';
            } elseif ($due->isPast()) {
                $status = 'expired';
            } elseif ($due->lte($now->copy()->addDays(30))) {
                $status = 'expiring_soon';
            }

            $lastCalibration = $inst->calibrations->where('lab_client_id', $client->id)->first();

            return [
                'id' => $inst->id,
                'name' => $inst->name,
                'serial_number' => $inst->serial_number,
                'tag' => $inst->location,
                'type' => $inst->instrumentType?->name,
                'status' => $status,
                'calibration_due' => $due?->format('d/m/Y'),
                'last_calibration' => $lastCalibration?->calibration_date?->format('d/m/Y'),
                'last_certificate_code' => $lastCalibration?->certificate_code,
            ];
        }));
    }

    /**
     * Resolve o cliente autenticado via token Sanctum, garantindo isolamento total.
     */
    protected function getAuthenticatedClient(Request $request, ?string $fallbackClientId = null): LabClient
    {
        $user = $request->user();

        if ($user instanceof LabClient) {
            return $user;
        }

        // Se passado fallbackClientId, verifica se o token fornecido confere
        if ($fallbackClientId) {
            $client = LabClient::find($fallbackClientId);
            if ($client && $user && $user->id === $client->id) {
                return $client;
            }
        }

        abort(401, 'Acesso não autorizado ao portal do cliente.');
    }
}
