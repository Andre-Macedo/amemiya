<?php

declare(strict_types=1);

namespace Modules\System\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TenantCertificateStorage
{
    /**
     * Decripta e retorna o conteúdo do certificado PEM a partir do caminho relativo ou absoluto.
     * Suporta tanto arquivos criptografados em repouso (Crypt::encryptString)
     * quanto arquivos legados em texto puro (iniciando com '-----BEGIN').
     */
    public function getDecryptedContent(string $path): ?string
    {
        $raw = null;

        if (Storage::disk('local')->exists($path)) {
            $raw = Storage::disk('local')->get($path);
        } elseif (file_exists($path)) {
            $raw = file_get_contents($path);
        } elseif (file_exists(storage_path("app/{$path}"))) {
            $raw = file_get_contents(storage_path("app/{$path}"));
        }

        if ($raw === null || $raw === false || $raw === '') {
            return null;
        }

        // Se já está em texto puro PEM legado
        if (str_starts_with(trim((string) $raw), '-----BEGIN')) {
            return (string) $raw;
        }

        // Tenta decriptar o conteúdo criptografado em repouso
        try {
            return Crypt::decryptString((string) $raw);
        } catch (Throwable $e) {
            logger()->error("Falha ao decriptar certificado digital: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Executa uma função fornecendo um caminho temporário com o certificado decriptado.
     * O arquivo temporário é garantidamente removido no bloco finally.
     *
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    public function withDecryptedTemporaryFile(string $path, callable $callback): mixed
    {
        $decryptedContent = $this->getDecryptedContent($path);

        if ($decryptedContent === null) {
            throw new \RuntimeException("Não foi possível carregar o conteúdo do certificado digital no caminho [{$path}].");
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'cert_sec_').'.pem';
        file_put_contents($tmpFile, $decryptedContent);
        @chmod($tmpFile, 0600);

        try {
            return $callback($tmpFile);
        } finally {
            if (file_exists($tmpFile)) {
                // Sobrescreve com zeros antes do unlink para higiene de memória forense
                @file_put_contents($tmpFile, str_repeat("\0", strlen($decryptedContent)));
                @unlink($tmpFile);
            }
        }
    }
}
