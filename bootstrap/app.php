<?php

use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'email.verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $statusCode = 500;
                $errorCode = 'API-ERR-500';
                $message = 'Internal Server Error';
                $errors = null;

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $statusCode = 422;
                    $errorCode = 'API-ERR-422';
                    $message = $e->getMessage();
                    $errors = $e->errors();
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    $statusCode = $e->getStatusCode();
                    $errorCode = 'API-ERR-'.$statusCode;
                    $message = $e->getMessage() ?: 'HTTP Error '.$statusCode;
                } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $statusCode = 404;
                    $errorCode = 'API-ERR-404';
                    $message = 'Data not found.';
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $statusCode = 401;
                    $errorCode = 'API-ERR-401';
                    $message = 'Unauthenticated.';
                } else {
                    $message = config('app.debug') ? $e->getMessage() : 'Internal Server Error';
                }
                
                $response = [
                    'status' => 'ERROR',
                    'code' => $statusCode,
                    'error_code' => $errorCode,
                    'message' => $message,
                    'path' => '/'.ltrim($request->path(), '/'),
                    'timestamp' => now()->format('Y-m-d\TH:i:s.u\Z'),
                ];
                
                if ($errors !== null) {
                    $response['errors'] = $errors;
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
