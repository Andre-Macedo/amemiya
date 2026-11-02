<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de guarda estrita para rotas protegidas por Tenancy.
 *
 * Garante que a requisição possua um tenant inicializado (via X-Tenant-ID, domínio ou usuário).
 * Impede que requisições desprovidas de identificação de tenant acessem modelos
 * que utilizam o Global Scope BelongsToTenant, prevenindo vazamento de dados entre empresas.
 */
class EnsureTenantIsInitialized
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantIdOrSlug = $request->header('X-Tenant-ID');

        if ($tenantIdOrSlug !== null && $tenantIdOrSlug !== '') {
            $tenantModel = config('tenancy.tenant_model');

            try {
                $tenant = $tenantModel::where('id', $tenantIdOrSlug)
                    ->orWhere('slug', $tenantIdOrSlug)
                    ->first();

                if (! $tenant) {
                    return new JsonResponse([
                        'message' => 'O tenant informado no cabeçalho X-Tenant-ID não foi encontrado.',
                        'error' => 'TENANT_NOT_FOUND',
                    ], Response::HTTP_BAD_REQUEST);
                }

                tenancy()->initialize($tenant);

                return $next($request);
            } catch (\Throwable) {
                return new JsonResponse([
                    'message' => 'Erro ao processar identificação do tenant.',
                    'error' => 'TENANT_INITIALIZATION_ERROR',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Se não forneceu o header, verifica se o Tenancy já foi inicializado (ex: domínio, sessão ou contexto prévio)
        if (tenancy()->initialized) {
            return $next($request);
        }

        // Se o usuário autenticado já possui um tenant associado, inicializa o tenancy automaticamente
        $user = $request->user();
        if ($user) {
            if (! empty($user->tenant_id)) {
                $tenantModel = config('tenancy.tenant_model');
                try {
                    $tenant = $tenantModel::find($user->tenant_id);
                    if ($tenant) {
                        tenancy()->initialize($tenant);

                        return $next($request);
                    }
                } catch (\Throwable) {
                    // Segue para verificação de super admin
                }
            }

            // Usuário Super Admin tem permissão para auditoria global / operações centrais
            if ($user->hasRole('super_admin') || $user->hasRole('super-admin')) {
                return $next($request);
            }
        }

        return new JsonResponse([
            'message' => 'Cabeçalho X-Tenant-ID ausente ou inválido. O acesso a este recurso multi-tenant exige a identificação do tenant.',
            'error' => 'TENANT_NOT_INITIALIZED',
        ], Response::HTTP_BAD_REQUEST);
    }
}
