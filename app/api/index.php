<?php

define('LARAVEL_START', microtime(true));

// 1. Buat direktori /tmp untuk storage Laravel di Vercel (karena filesystem Vercel read-only)
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

// 2. Setup SQLite mandiri di /tmp jika tidak menggunakan database eksternal
$dbConnection = getenv('DB_CONNECTION') ?: 'sqlite';
if ($dbConnection === 'sqlite') {
    $dbPath = '/tmp/database.sqlite';
    putenv("DB_DATABASE={$dbPath}");
    $_ENV['DB_DATABASE'] = $dbPath;

    $isNewDb = !file_exists($dbPath);
    if ($isNewDb) {
        @touch($dbPath);
    }

    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    if ($isNewDb) {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        } catch (\Throwable $e) {
            // Silently ignore if already migrated
        }
    }

    $app->handleRequest(\Illuminate\Http\Request::capture());
    exit;
}

// 3. Jika menggunakan MySQL/PostgreSQL eksternal
require __DIR__ . '/../public/index.php';
