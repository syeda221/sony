<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$keys = ['company_ntn', 'company_strn'];
foreach ($keys as $k) {
    $exists = App\Models\Setting::where('key', $k)->first();
    if (!$exists) {
        App\Models\Setting::create([
            'group' => 'company',
            'key' => $k,
            'label' => strtoupper(str_replace('company_', '', $k)) . ' Number',
            'value' => '',
            'type' => 'text'
        ]);
        echo "Created setting: $k\n";
    } else {
        echo "Already exists: $k\n";
    }
}
