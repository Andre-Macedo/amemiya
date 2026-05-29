<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;

/**
 * Middleware customizado para inicializar o Tenancy via Header X-Tenant-ID.
 * Suporta tanto o ID (ULID) quanto o Slug da empresa.
 */
class InitializeTenancyByHeader
{
    protected $tenancy;

    public function __construct(Tenancy $tenancy)
    {
        $this->tenancy = $tenancy;
    }

    public function handle(Request $request, Closure $next)
    {
        $tenantIdOrSlug = $request->header('X-Tenant-ID');

        if ($tenantIdOrSlug) {
            $tenantModel = config('tenancy.tenant_model');
            
            try {
                // Tenta encontrar por ID ou por Slug
                $tenant = $tenantModel::where('id', $tenantIdOrSlug)
                    ->orWhere('slug', $tenantIdOrSlug)
                    ->first();

                if ($tenant) {
                    $this->tenancy->initialize($tenant);
                    return $next($request);
                }
            } catch (\Exception $e) {
                // Se falhar a identificação, deixamos passar para ver se é um Super Admin
            }
        }

        // Continua a requisição mesmo se não houver tenant (acesso Global/Super Admin)
        return $next($request);
    }
}
