<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = App\Models\User::with('roles')->get();
foreach ($users as $u) {
    echo "ID: {$u->id}, Name: {$u->name}, Email: {$u->email}, Roles: " . implode(', ', $u->roles->pluck('name')->toArray()) . "\n";
}

$roles = Spatie\Permission\Models\Role::with('permissions')->get();
foreach ($roles as $r) {
    echo "Role: {$r->name} (ID: {$r->id}), Has provinces.view: " . ($r->hasPermissionTo('provinces.view') ? 'YES' : 'NO') . "\n";
}
