<?php

/**
 * Vercel serverless entry point.
 *
 * Vercel's filesystem is read-only apart from /tmp, and /tmp does not survive
 * between invocations. The storage tree Laravel writes to is therefore rebuilt
 * on every cold start and the framework is pointed at it through
 * LARAVEL_STORAGE_PATH, which Application::storagePath() reads from $_ENV.
 *
 * Nothing written here is durable. Sessions, cache and queue all run on the
 * database connection for that reason, and logs go to stderr.
 */
$storagePath = '/tmp/storage';

$directories = [
    $storagePath.'/app/public',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
];

foreach ($directories as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0755, recursive: true);
    }
}

$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

require __DIR__.'/../public/index.php';
