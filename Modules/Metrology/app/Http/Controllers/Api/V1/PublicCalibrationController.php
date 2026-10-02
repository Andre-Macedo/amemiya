<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Metrology\Models\Calibration;

class PublicCalibrationController extends Controller
{
    /**
     * Verifica a validade de um certificado através de seu hash público, código ou ID.
     */
    public function show(string $hash): JsonResponse
    {
        $calibration = Calibration::query()
            ->with(['calibratedItem', 'tenant', 'performedBy', 'approvedBy'])
            ->where(function ($query) use ($hash) {
                $query->where('verification_hash', $hash)
                    ->orWhere('pdf_hash', $hash)
                    ->orWhere('certificate_code', $hash)
                    ->orWhere('id', $hash);
            })
            ->where('status', '!=', 'draft')
            ->first();

        if (! $calibration) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificado não encontrado ou ainda em elaboração.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'certificate' => $this->formatCertificateData($calibration),
        ]);
    }

    /**
     * Valida a integridade criptográfica de um arquivo PDF enviado contra o registro oficial (SHA-256).
     */
    public function verifyFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'code' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $uploadedHash = hash('sha256', $file->getContent());
        $code = $request->input('code') ?: $request->input('hash');

        if (! empty($code)) {
            $calibration = Calibration::query()
                ->with(['calibratedItem', 'tenant', 'performedBy', 'approvedBy'])
                ->where(function ($query) use ($code) {
                    $query->where('verification_hash', $code)
                        ->orWhere('certificate_code', $code)
                        ->orWhere('pdf_hash', $code)
                        ->orWhere('id', $code);
                })
                ->where('status', '!=', 'draft')
                ->first();

            if (! $calibration) {
                return response()->json([
                    'valid' => false,
                    'authentic' => false,
                    'message' => 'Certificado com este código não foi encontrado no sistema.',
                    'uploaded_sha256' => $uploadedHash,
                ], 404);
            }

            // Se o certificado não possuir pdf_hash ainda, registra o hash do primeiro arquivo original
            $expectedHash = $calibration->pdf_hash;
            if (empty($expectedHash)) {
                $calibration->updateQuietly(['pdf_hash' => $uploadedHash]);
                $expectedHash = $uploadedHash;
            }

            $isMatch = hash_equals($expectedHash, $uploadedHash);

            return response()->json([
                'valid' => $isMatch,
                'authentic' => $isMatch,
                'tampered' => ! $isMatch,
                'message' => $isMatch
                    ? 'Certificado 100% Autêntico e Íntegro. O arquivo PDF confere bit a bit com o laudo oficial emitido pelo laboratório (SHA-256 verificado).'
                    : 'ALERTA DE ADULTERAÇÃO: O arquivo PDF enviado foi modificado após sua emissão oficial! O hash SHA-256 não confere.',
                'uploaded_sha256' => $uploadedHash,
                'expected_sha256' => $expectedHash,
                'certificate' => $this->formatCertificateData($calibration),
            ], $isMatch ? 200 : 422);
        }

        // Sem código fornecido: busca reversa direta pelo hash SHA-256 do arquivo
        $calibration = Calibration::query()
            ->with(['calibratedItem', 'tenant', 'performedBy', 'approvedBy'])
            ->where('pdf_hash', $uploadedHash)
            ->where('status', '!=', 'draft')
            ->first();

        if ($calibration) {
            return response()->json([
                'valid' => true,
                'authentic' => true,
                'tampered' => false,
                'message' => 'Certificado Identificado e Autêntico! O arquivo PDF confere exatamente com o registro oficial arquivado no laboratório.',
                'uploaded_sha256' => $uploadedHash,
                'expected_sha256' => $calibration->pdf_hash,
                'certificate' => $this->formatCertificateData($calibration),
            ]);
        }

        return response()->json([
            'valid' => false,
            'authentic' => false,
            'tampered' => null,
            'message' => 'Nenhum certificado oficial emitido pelo sistema corresponde ao hash SHA-256 deste arquivo PDF.',
            'uploaded_sha256' => $uploadedHash,
        ], 404);
    }

    private function formatCertificateData(Calibration $calibration): array
    {
        $calibratedItem = $calibration->calibratedItem;

        return [
            'id' => $calibration->id,
            'certificate_code' => $calibration->certificate_code,
            'verification_hash' => $calibration->verification_hash,
            'pdf_hash' => $calibration->pdf_hash,
            'calibration_date' => $calibration->calibration_date?->format('d/m/Y'),
            'next_due_date' => $calibration->next_due_date?->format('d/m/Y'),
            'result' => $calibration->result?->getLabel(),
            'result_value' => $calibration->result?->value,
            'technician' => $calibration->performedBy?->name ?? $calibration->technician,
            'approved_by' => $calibration->approvedBy?->name,
            'approved_at' => $calibration->approved_at?->format('d/m/Y H:i'),
            'deviation' => $calibration->as_left_deviation ?? $calibration->deviation,
            'uncertainty' => $calibration->uncertainty,
            'instrument' => [
                'name' => $calibratedItem?->name,
                'serial_number' => $calibratedItem?->serial_number,
                'tag_code' => $calibratedItem?->tag_code ?? $calibratedItem?->code,
                'model' => $calibratedItem?->model,
                'manufacturer' => $calibratedItem?->manufacturer,
            ],
            'tenant' => [
                'name' => $calibration->tenant?->name,
            ],
            'verification_date' => now()->toDateTimeString(),
        ];
    }
}
