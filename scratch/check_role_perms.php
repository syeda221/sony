<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$roles = Spatie\Permission\Models\Role::all();
foreach ($roles as $r) {
    echo "Role: {$r->name} (id: {$r->id})\n";
    $p = $r->permissions->pluck('name')->toArray();
    $prov = array_filter($p, fn($x) => str_contains($x, 'province'));
    echo "  Province perms: " . json_encode(array_values($prov)) . "\n";
    $zones = array_filter($p, fn($x) => str_contains($x, 'zone'));
    echo "  Zone perms: " . json_encode(array_values($zones)) . "\n";
}
