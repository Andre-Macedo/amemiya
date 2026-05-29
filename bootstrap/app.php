<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // O Tenancy será inicializado apenas onde for explicitamente necessário (routes/api.php).
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });

        // Oculta detalhes do SQL em produção, mas mostra em desenvolvimento
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*') && ! config('app.debug')) {
                return response()->json([
                    'message' => 'Ocorreu um erro interno ao processar os dados no servidor. Por favor, tente novamente mais tarde.',
                    'status' => 500,
                ], 500);
            }
        });
    })->create();
