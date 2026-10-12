<?php

use App\Http\Middleware\BlockMemberAccessToPanel;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

// HAPUS baris-baris ini karena tidak perlu:
// use Symfony\Component\ErrorHandler\Error\FatalError;
// use ErrorException;
// use ParseError;
// use TypeError;
// use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(function () {});
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'block-member' => BlockMemberAccessToPanel::class,
        ]);
        $middleware->alias(['set.locale' => SetLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ============================================================
        // 1. DETEKSI RESPONSE TYPE (JSON atau VIEW)
        // ============================================================
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->expectsJson() || $request->is('api/*');
        });

        // ============================================================
        // 2. HANDLER UNTUK MAINTENANCE MODE (503)
        // ============================================================
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 503) {
                // API Response
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sedang dalam pemeliharaan, akan segera kembali',
                        'status_code' => 503
                    ], 503);
                }

                // Web Response
                if (view()->exists('errors.503')) {
                    return response()->view('errors.503', [], 503);
                }

                return response()->view('errors.503', [], 503);
            }
            return null;
        });

        // ============================================================
        // 3. HANDLER UNTUK VALIDATION ERROR (422)
        // ============================================================
        $exceptions->render(function (ValidationException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'status_code' => 422
                ], 422);
            }

            // Web Response - Redirect back with errors
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        });

        // ============================================================
        // 4. HANDLER UNTUK UNAUTHENTICATED (401)
        // ============================================================
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'status_code' => 401
                ], 401);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            return redirect()->guest(route('login'));
        });

        // ============================================================
        // 5. HANDLER UNTUK FORBIDDEN (403)
        // ============================================================
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Forbidden',
                    'status_code' => 403
                ], 403);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.403')) {
                return response()->view('errors.403', [
                    'message' => $e->getMessage() ?: 'Akses ditolak'
                ], 403);
            }

            return response()->view('errors.403', [], 403);
        });

        // ============================================================
        // 6. HANDLER UNTUK MODEL NOT FOUND (404)
        // ============================================================
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => "{$model} not found",
                    'status_code' => 404
                ], 404);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.404')) {
                return response()->view('errors.404', [
                    'message' => $e->getMessage() ?: 'Halaman tidak ditemukan'
                ], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        // ============================================================
        // 7. HANDLER UNTUK ROUTE NOT FOUND (404)
        // ============================================================
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint not found',
                    'status_code' => 404
                ], 404);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.404')) {
                return response()->view('errors.404', [
                    'message' => 'Halaman tidak ditemukan'
                ], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        // ============================================================
        // 8. HANDLER UNTUK METHOD NOT ALLOWED (405)
        // ============================================================
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed',
                    'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
                    'status_code' => 405
                ], 405);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.405')) {
                return response()->view('errors.405', [
                    'message' => 'Method not allowed'
                ], 405);
            }

            return response()->view('errors.404', [], 405);
        });

        // ============================================================
        // 9. HANDLER UNTUK SESSION EXPIRED / CSRF (419)
        // ============================================================
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired',
                    'status_code' => 419
                ], 419);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.419')) {
                return response()->view('errors.419', [
                    'message' => 'Sesi berakhir, silakan refresh halaman'
                ], 419);
            }

            return redirect()->back()->with('error', 'Session expired, please try again');
        });

        // ============================================================
        // 10. HANDLER UNTUK TOO MANY REQUESTS (429)
        // ============================================================
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests',
                    'retry_after' => $retryAfter,
                    'status_code' => 429
                ], 429);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.429')) {
                return response()->view('errors.429', [
                    'message' => 'Terlalu banyak permintaan, silakan coba lagi nanti'
                ], 429);
            }

            return response()->view('errors.429', [], 429);
        });

        // ============================================================
        // 11. HANDLER UNTUK DATABASE ERROR (500)
        // ============================================================
        $exceptions->render(function (QueryException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                $message = app()->environment('production')
                    ? 'Database error occurred'
                    : $e->getMessage();

                $response = [
                    'success' => false,
                    'message' => $message,
                    'status_code' => 500
                ];

                if (config('app.debug')) {
                    $response['sql'] = $e->getSql();
                    $response['bindings'] = $e->getBindings();
                }

                return response()->json($response, 500);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.500')) {
                return response()->view('errors.500', [
                    'message' => 'Terjadi kesalahan pada database'
                ], 500);
            }

            return response()->view('errors.500', [], 500);
        });

        // ============================================================
        // 12. HANDLER UNTUK HTTP EXCEPTION LAINNYA
        // ============================================================
        $exceptions->render(function (HttpException $e, Request $request) {
            $statusCode = $e->getStatusCode();

            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'HTTP Error',
                    'status_code' => $statusCode
                ], $statusCode);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            // Cek apakah ada view khusus untuk status code ini
            if (view()->exists("errors.{$statusCode}")) {
                return response()->view("errors.{$statusCode}", [
                    'message' => $e->getMessage()
                ], $statusCode);
            }

            // Fallback ke error 500
            if (view()->exists('errors.500')) {
                return response()->view('errors.500', [
                    'message' => 'Terjadi kesalahan pada server'
                ], $statusCode);
            }

            return null;
        });

        // ============================================================
        // 13. HANDLER UNTUK PHP ERRORS (ErrorException, TypeError, ParseError, dll)
        // Gunakan class global tanpa use statement
        // ============================================================
        $exceptions->render(function (\ErrorException $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                $message = app()->environment('production')
                    ? 'Server error occurred'
                    : $e->getMessage();

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'status_code' => 500
                ], 500);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.500')) {
                return response()->view('errors.500', [
                    'message' => 'Terjadi kesalahan pada server'
                ], 500);
            }

            return null;
        });

        $exceptions->render(function (\TypeError $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                $message = app()->environment('production')
                    ? 'Type error occurred'
                    : $e->getMessage();

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'status_code' => 500
                ], 500);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.500')) {
                return response()->view('errors.500', [
                    'message' => 'Terjadi kesalahan pada server'
                ], 500);
            }

            return null;
        });

        $exceptions->render(function (\ParseError $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                $message = app()->environment('production')
                    ? 'Parse error occurred'
                    : $e->getMessage();

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'status_code' => 500
                ], 500);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel
            }

            if (view()->exists('errors.500')) {
                return response()->view('errors.500', [
                    'message' => 'Terjadi kesalahan pada server'
                ], 500);
            }

            return null;
        });

        // ============================================================
        // 14. GLOBAL HANDLER UNTUK SEMUA EXCEPTION YANG BELUM TERTANGANI
        // ============================================================
        $exceptions->render(function (\Throwable $e, Request $request) {
            // API Response
            if ($request->expectsJson() || $request->is('api/*')) {
                $message = app()->environment('production')
                    ? 'An error occurred'
                    : $e->getMessage();

                $response = [
                    'success' => false,
                    'message' => $message,
                    'status_code' => 500
                ];

                if (config('app.debug')) {
                    $response['exception'] = get_class($e);
                    $response['file'] = $e->getFile();
                    $response['line'] = $e->getLine();
                    $response['trace'] = collect($e->getTrace())->map(function ($trace) {
                        return [
                            'file' => $trace['file'] ?? null,
                            'line' => $trace['line'] ?? null,
                            'function' => $trace['function'] ?? null,
                            'class' => $trace['class'] ?? null,
                        ];
                    })->take(10)->toArray();
                }

                return response()->json($response, 500);
            }

            // Web Response
            if (config('app.debug')) {
                return null; // Tampilkan error detail Laravel (Whoops)
            }

            // Production: Tampilkan error 500
            if (view()->exists('errors.500')) {
                return response()->view('errors.500', [
                    'message' => 'Terjadi kesalahan pada server'
                ], 500);
            }

            return response()->view('errors.500', [], 500);
        });

        // ============================================================
        // 15. REPORTING (LOG ERROR)
        // ============================================================

        // Jangan report exceptions yang umum di production
        $exceptions->dontReport([
            ValidationException::class,
            AuthenticationException::class,
            AuthorizationException::class,
            ModelNotFoundException::class,
            NotFoundHttpException::class,
            MethodNotAllowedHttpException::class,
            TokenMismatchException::class,
            ThrottleRequestsException::class,
            TooManyRequestsHttpException::class,
        ]);

        // Report error yang perlu dilog
        $exceptions->reportable(function (Throwable $e) {
            // Cek apakah exception ini perlu dilog
            $shouldLog = true;
            $ignoreList = [
                ValidationException::class,
                AuthenticationException::class,
                AuthorizationException::class,
                ModelNotFoundException::class,
                NotFoundHttpException::class,
                MethodNotAllowedHttpException::class,
                TokenMismatchException::class,
                ThrottleRequestsException::class,
            ];

            foreach ($ignoreList as $ignore) {
                if ($e instanceof $ignore) {
                    $shouldLog = false;
                    break;
                }
            }

            if ($shouldLog) {
                Log::error('Application Error: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        });

        // Report untuk error kritis dengan level berbeda
        $exceptions->reportable(function (QueryException $e) {
            Log::critical('Database Error: ' . $e->getMessage(), [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'url' => request()->fullUrl(),
            ]);
        });

        $exceptions->reportable(function (\ErrorException $e) {
            Log::critical('PHP Error: ' . $e->getMessage(), [
                'severity' => $e->getSeverity(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });
    })->create();
