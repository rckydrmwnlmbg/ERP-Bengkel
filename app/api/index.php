<?php

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

// 2. Load Composer Autoloader & Bootstrap Laravel Application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 3. Tangani request HTTP masuk (Stateless, tanpa migrasi/seeder otomatis saat boot)
$app->handleRequest(\Illuminate\Http\Request::capture());
