<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$settings = App\Models\Setting::getAllGrouped();
echo "Group company exists: " . (isset($settings['company']) ? "YES" : "NO") . "\n";
if (isset($settings['company'])) {
    $keys = array_column($settings['company'], 'key');
    echo "Keys in settings['company']: " . implode(', ', $keys) . "\n";
}
