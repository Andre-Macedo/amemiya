<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se não houver um tenant inicializado ou se o usuário for um Super Admin, permitimos o acesso
        if (! tenancy()->initialized || ($request->user() && $request->user()->hasRole('super-admin'))) {
            return $next($request);
        }

        $tenant = tenancy()->tenant;

        // Verifica se o tenant tem acesso operacional (Assinatura Ativa ou Trial)
        if (! $tenant->hasAccess()) {
            error_log("Subscription FAILED for tenant: " . $tenant->slug);

            // Se for uma requisição de API, retornamos 402 Payment Required
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Assinatura necessária ou expirada. Por favor, regularize seu pagamento para continuar acessando o sistema.',
                    'status' => 'payment_required',
                    'tenant_id' => $tenant->id,
                ], 402);
            }
        }

        return $next($request);
    }
}
