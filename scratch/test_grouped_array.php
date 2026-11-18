<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$settings = App\Models\Setting::getAllGrouped();
$compByKey = [];
if (isset($settings['company'])) {
    foreach ($settings['company'] as $s) {
        $compByKey[$s['key']] = $s;
    }
}

echo "company_name in compByKey: " . (isset($compByKey['company_name']) ? "YES, value=" . $compByKey['company_name']['value'] : "NO") . "\n";
echo "company_phone in compByKey: " . (isset($compByKey['company_phone']) ? "YES" : "NO") . "\n";
