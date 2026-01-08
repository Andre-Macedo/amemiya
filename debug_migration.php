<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

// Force SQLite
config(['database.default' => 'sqlite']);
config(['database.connections.sqlite.database' => ':memory:']);

echo "Running migrations on SQLite :memory: ...\n";

try {
    Artisan::call('migrate', ['--force' => true]);
    echo "Migrations success!\n";
} catch (\Exception $e) {
    echo "Migration Failed!\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
