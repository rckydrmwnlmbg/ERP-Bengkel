<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // TEMPORARY - hapus setelah diagnosis selesai
        $exceptions->report(function (\Throwable $e): bool {
            try {
                $class = get_class($e);
                $message = mb_substr(str_replace(["\r\n", "\r", "\n"], ' ', $e->getMessage()), 0, 300);

                $basePath = base_path();
                $parentPath = dirname($basePath);
                $file = $e->getFile();
                if (str_starts_with($file, $basePath . DIRECTORY_SEPARATOR)) {
                    $file = substr($file, strlen($basePath . DIRECTORY_SEPARATOR));
                } elseif (str_starts_with($file, $parentPath . DIRECTORY_SEPARATOR)) {
                    $file = substr($file, strlen($parentPath . DIRECTORY_SEPARATOR));
                }

                $line = $e->getLine();

                $request = app()->has('request') ? app('request') : null;
                $method = ($request instanceof Request) ? $request->method() : (app()->runningInConsole() ? 'CLI' : 'UNKNOWN');
                $path = ($request instanceof Request) ? $request->path() : implode(' ', $_SERVER['argv'] ?? ['artisan']);

                $conn = config('database.default');
                $session = config('session.driver');
                $cache = config('cache.default');
                $host = gethostname();
                $pid = getmypid();

                $diag = sprintf(
                    '[DIAG] %s | %s | %s:%d | %s %s | conn=%s | session=%s | cache=%s | host=%s | pid=%s',
                    $class,
                    $message,
                    $file,
                    $line,
                    $method,
                    $path,
                    $conn,
                    $session,
                    $cache,
                    $host,
                    $pid
                );

                if (defined('STDERR') && is_resource(STDERR)) {
                    @fwrite(STDERR, $diag . PHP_EOL);
                } else {
                    @file_put_contents('php://stderr', $diag . PHP_EOL);
                }
            } catch (\Throwable) {
                // Menjamin kegagalan pencatatan diagnostik tidak mengganggu alur aplikasi
            }

            return false;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
