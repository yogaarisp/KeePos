<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\IdentifyTenant;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->alias([
            'role' => CheckRole::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'plan' => \App\Http\Middleware\CheckPlan::class,
        ]);
        $middleware->appendToGroup('web', [
            IdentifyTenant::class,
        ]);
        $middleware->appendToGroup('api', [
            IdentifyTenant::class,
            // Don't apply CheckSubscription globally - apply it per route group instead
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'status' => 'error',
                    'code' => 401
                ], 401);
            }
        });
    })->create();

