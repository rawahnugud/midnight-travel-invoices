#!/bin/sh
# Run in the existing installation after checking out this release.
set -eu
cd "$(dirname "$0")/.."
test -r vendor/autoload.php || { echo "Existing PHP dependencies are required."; exit 1; }
php -r 'require "vendor/autoload.php"; if (PHP_VERSION_ID < 80100 || !extension_loaded("pdo_sqlite") || !str_starts_with(Illuminate\Foundation\Application::VERSION, "10.")) { fwrite(STDERR, "Expected PHP 8.1+ with PDO SQLite and the installed Laravel 10 runtime.\n"); exit(1); }'
composer check-platform-reqs --no-dev
composer dump-autoload --no-scripts --optimize --no-interaction
php artisan config:clear
php artisan clear-compiled
php artisan package:discover
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $db = $app["db"]->connection(); if ($db->getDriverName() !== "sqlite" || !is_file($db->getDatabaseName()) || !$db->getSchemaBuilder()->hasTable("invoices")) { fwrite(STDERR, "Expected the existing SQLite invoice database. Check the installation settings.\n"); exit(1); }'
php artisan migrate --force
php artisan config:cache
