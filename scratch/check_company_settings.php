<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$settings = App\Models\Setting::where('group', 'company')->get();
echo "Company settings count: " . $settings->count() . "\n";
foreach ($settings as $s) {
    echo "- Key: {$s->key}, Label: {$s->label}, Value: {$s->value}\n";
}
