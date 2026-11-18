<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$settings = App\Models\Setting::where('group', 'company')->get();
$keys = ['company_name', 'company_phone', 'company_ntn', 'currency_symbol', 'company_strn', 'company_address'];
foreach ($keys as $k) {
    $s = $settings->firstWhere('key', $k);
    if ($s) {
        echo "Found $k: id={$s->id}, value='{$s->value}', label='{$s->label}'\n";
    } else {
        echo "NOT FOUND: $k\n";
    }
}
