<?php

/**
 * Vercel serverless entry point.
 *
 * The deployment directory is read-only and only /tmp is writable, so the two
 * directories Laravel writes to are staged there on every cold start:
 *
 * - storage: sessions, cache and compiled views. Nothing here is durable, which
 *   is why sessions, cache and queue all run on the database connection and logs
 *   go to stderr.
 * - bootstrap: the package and service manifests. Composer's post-autoload-dump
 *   scripts do not run on this runtime, so `package:discover` never fires and
 *   Laravel builds these on the first request. Pointing them at /tmp lets that
 *   write succeed and rebuilds them from the packages actually installed, rather
 *   than from a copy carrying dev-only providers such as Laravel Boost.
 */
$storagePath = '/tmp/storage';
$bootstrapPath = '/tmp/bootstrap';

$directories = [
    $storagePath.'/app/public',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
    $bootstrapPath.'/cache',
];

foreach ($directories as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0755, recursive: true);
    }
}

/**
 * The provider list is read from the bootstrap path, so it has to travel with it.
 */
$providers = __DIR__.'/../bootstrap/providers.php';

if (is_file($providers) && ! is_file($bootstrapPath.'/providers.php')) {
    copy($providers, $bootstrapPath.'/providers.php');
}

foreach (['LARAVEL_STORAGE_PATH' => $storagePath, 'LARAVEL_BOOTSTRAP_PATH' => $bootstrapPath] as $key => $value) {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../public/index.php';
