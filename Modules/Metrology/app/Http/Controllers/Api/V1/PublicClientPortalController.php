<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\LabClient;
use Modules\System\Models\Setting;

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
            return response()->json(['message' => 'Credenciais de acesso inválidas.'], 401);
        }

        // Gera um token temporário ou apenas retorna os dados (Portal é Read-only)
        // Para simplificar agora, retornaremos o perfil e o branding do laboratório

        tenancy()->initialize($client->tenant);

        $branding = [
            'lab_name' => Setting::getValue('lab_name', $client->tenant->name),
            'lab_logo_url' => Setting::getValue('lab_logo_path') ? Storage::disk('public')->url(Setting::getValue('lab_logo_path')) : null,
            'accent_color' => Setting::getValue('lab_accent_color', '#3b82f6'),
        ];

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'tenant_id' => $client->tenant_id,
            ],
            'branding' => $branding,
            'auth_token' => 'portal_'.$client->id.'_'.bin2hex(random_bytes(16)), // Token dummy para o front gerenciar sessão
        ]);
    }

    /**
     * Lista os certificados disponíveis para o cliente logado no portal.
     */
    public function certificates(Request $request, string $clientId): JsonResponse
    {
        // Aqui em produção você validaria o auth_token
        $client = LabClient::findOrFail($clientId);

        $calibrations = Calibration::where('lab_client_id', $client->id)
            ->where('status', 'published')
            ->with('calibratedItem')
            ->latest()
            ->get();

        return response()->json($calibrations->map(fn ($cal) => [
            'id' => $cal->id,
            'instrument' => $cal->calibratedItem?->name,
            'serial_number' => $cal->calibratedItem?->serial_number,
            'date' => $cal->calibration_date->format('d/m/Y'),
            'result' => $cal->result->getLabel(),
            'pdf_url' => $cal->certificate_path ? Storage::disk('local')->url($cal->certificate_path) : null,
        ]));
    }
}
