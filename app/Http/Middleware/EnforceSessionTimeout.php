<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return $next($request);
        }

        if (! Auth::check()) {
            return $next($request);
        }

        $session = $request->session();
        $lastActivity = $session->get('last_activity_time');
        $timeoutMinutes = (int) config('session.inactivity_timeout', 30);

        if ($lastActivity !== null) {
            $elapsedSeconds = Carbon::now()->timestamp - (int) $lastActivity;
            $idleMinutes = (int) floor($elapsedSeconds / 60);

            if ($idleMinutes >= $timeoutMinutes) {
                Auth::guard('web')->logout();
                $session->invalidate();
                $session->regenerateToken();

                if ($request->expectsJson()) {
                    return new JsonResponse([
                        'message' => 'Sua sessão expirou por inatividade. Faça login novamente.',
                        'error' => 'SESSION_EXPIRED',
                    ], Response::HTTP_UNAUTHORIZED);
                }

                $loginUrl = Route::has('filament.admin.auth.login')
                    ? route('filament.admin.auth.login')
                    : '/login';

                return redirect()->to($loginUrl)->withErrors([
                    'email' => 'Sua sessão expirou por inatividade (conformidade ISO 17025). Por favor, realize novo login.',
                ]);
            }
        }

        $session->put('last_activity_time', Carbon::now()->timestamp);

        return $next($request);
    }
}
