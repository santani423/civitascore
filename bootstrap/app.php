<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureUniversityAccessMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveUniversityMiddleware;
use App\Support\Http\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'permission' => EnsurePermission::class,
            'tenant.resolve' => ResolveUniversityMiddleware::class,
            'tenant.access' => EnsureUniversityAccessMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Routes/exceptions that already define their own render() (e.g.
        // App\Support\Http\Exceptions\ConflictException) take priority over
        // this callback (see Handler::render()), so it only needs to cover
        // framework exceptions that don't. Handler::prepareException() also
        // runs first and already converts AuthorizationException ->
        // AccessDeniedHttpException and ModelNotFoundException ->
        // NotFoundHttpException before this callback ever sees them — match
        // on the converted types, not the originals, or these branches are
        // silently unreachable.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => ApiResponse::error('Validasi data gagal.', $e->errors(), $e->status),
                $e instanceof AuthenticationException => ApiResponse::error('Tidak terautentikasi.', status: 401),
                $e instanceof AccessDeniedHttpException => ApiResponse::error($e->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses resource ini.', status: 403),
                $e instanceof NotFoundHttpException => ApiResponse::error('Data tidak ditemukan.', status: 404),
                $e instanceof ThrottleRequestsException => ApiResponse::error('Terlalu banyak permintaan. Coba lagi nanti.', status: 429),
                // Catch-all so any unhandled exception on api/* still comes back as
                // the app's standard { success, message, data, errors } JSON envelope
                // instead of Laravel's bare default error body — without this, an
                // unexpected failure (e.g. misconfigured env on a fresh deploy) looks
                // to the frontend like the request silently failed.
                default => ApiResponse::error(
                    config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
                    status: $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500,
                ),
            };
        });
    })->create();
