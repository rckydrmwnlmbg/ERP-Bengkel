<?php

declare(strict_types=1);

define('LARAVEL_START', microtime(true));

// 1. Buat direktori /tmp untuk storage Laravel di Vercel (karena filesystem serverless Vercel read-only)
$tmpDirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/cache',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($tmpDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
putenv('APP_STORAGE=/tmp/storage');
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
$_ENV['APP_STORAGE'] = '/tmp/storage';

try {
    // 2. Load Composer Autoloader & Bootstrap Laravel Application
    require __DIR__ . '/../vendor/autoload.php';
    /** @var \Illuminate\Foundation\Application $app */
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    // Arahkan storage dan bootstrap cache ke /tmp (writable di serverless Vercel)
    $app->useStoragePath('/tmp/storage');
    $app->useBootstrapPath('/tmp/bootstrap');

    // 3. Tangani request HTTP masuk
    $app->handleRequest(\Illuminate\Http\Request::capture());
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>500 Server Error - ERP Diagnostic</title>";
    echo "<style>body{font-family:system-ui,-apple-system,sans-serif;padding:30px;background:#0f172a;color:#f8fafc;}";
    echo ".card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:24px;max-width:900px;margin:0 auto;}";
    echo "h1{color:#ef4444;margin-top:0;}h3{color:#cbd5e1;}pre{background:#020617;padding:16px;border-radius:6px;overflow-x:auto;color:#38bdf8;font-size:13px;}";
    echo ".badge{background:#dc2626;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold;}";
    echo "</style></head><body><div class='card'>";
    echo "<h1><span class='badge'>FATAL 500</span> Error pada Server Vercel</h1>";
    echo "<h3>" . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<p><strong>Lokasi:</strong> " . htmlspecialchars($e->getFile()) . " : baris " . $e->getLine() . "</p>";
    echo "<h4>Stack Trace:</h4>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div></body></html>";
    exit(1);
}
