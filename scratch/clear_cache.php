<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\Artisan::call('view:clear');
echo "View Cache: " . \Illuminate\Support\Facades\Artisan::output();

\Illuminate\Support\Facades\Artisan::call('route:clear');
echo "Route Cache: " . \Illuminate\Support\Facades\Artisan::output();
