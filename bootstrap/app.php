<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__."/../routes/web.php",
        api: __DIR__."/../routes/api.php",
        commands: __DIR__."/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // HAProxy terminates SSL upstream; trust its X-Forwarded-* headers.
        $middleware->trustProxies(at: "*", headers:
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            "admin" => \App\Http\Middleware\AdminMiddleware::class,
            "staff.auth" => \App\Http\Middleware\StaffAuth::class,
            "manage.auth" => \App\Http\Middleware\VerifyManagementApiToken::class,
            "idempotency" => \App\Http\Middleware\IdempotencyKey::class,
        ]);

        // Security response headers on every browser-facing page.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Consistent error envelope for the management API.
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/v1/manage/*')) {
                return response()->json(['error' => [
                    'code' => 'validation_failed', 'message' => $e->getMessage(), 'details' => $e->errors(),
                ]], 422);
            }
        });
        $exceptions->render(function (\App\Exceptions\ProvisioningConflictException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/v1/manage/*')) {
                return response()->json(['error' => ['code' => 'conflict', 'message' => $e->getMessage()]], 409);
            }
        });
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/v1/manage/*')) {
                return response()->json(['error' => ['code' => 'not_found', 'message' => 'Resource not found.']], 404);
            }
        });
    })->create();
