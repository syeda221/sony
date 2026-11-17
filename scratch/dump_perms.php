<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$perms = Spatie\Permission\Models\Permission::pluck('name')->toArray();
echo json_encode($perms);
