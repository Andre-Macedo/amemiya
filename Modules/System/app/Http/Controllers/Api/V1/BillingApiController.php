<?php

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Metrology\Models\Instrument;
use Modules\System\Models\User;

class BillingApiController extends Controller
{
    /**
     * Retorna os detalhes da assinatura e uso do tenant atual.
     */
    public function index(): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $subscription = $tenant->activeSubscription;
        $plan = $subscription?->plan;

        // Cálculo de Uso Atual
        $instrumentCount = Instrument::count();
        $userCount = User::count();

        // Determinação de Limites (Plano ou Override)
        $limitInstruments = $tenant->limit_instruments_override ?? $plan?->max_instruments ?? 0;
        $limitUsers = $tenant->limit_users_override ?? $plan?->max_users ?? 0;

        return response()->json([
            'tenant' => [
                'name' => $tenant->name,
                'status' => $tenant->status,
            ],
            'plan' => $plan ? [
                'name' => $plan->name,
                'description' => $plan->description,
                'price' => $plan->price,
            ] : null,
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'next_billing_at' => $subscription->next_billing_at,
                'ends_at' => $subscription->ends_at,
                'trial_ends_at' => $subscription->trial_ends_at,
                'gateway' => $subscription->gateway,
            ] : null,
            'usage' => [
                'instruments' => [
                    'current' => $instrumentCount,
                    'limit' => $limitInstruments, // 0 = ilimitado
                ],
                'users' => [
                    'current' => $userCount,
                    'limit' => $limitUsers,
                ],
            ],
        ]);
    }
}
