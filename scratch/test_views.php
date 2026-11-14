<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

auth()->login(App\Models\User::first());

$sc = app(App\Http\Controllers\SaleController::class);
$editView = $sc->saleedit(10);
$html = $editView->render();

echo "Rendered Sale 10 Edit View: " . strlen($html) . " bytes\n";

// Check if ubl input has 250
if (preg_match('/name="receipt_amount\[\]"[^>]*value="250(\.00)?"/i', $html, $m)) {
    echo "SUCCESS: Found receipt_amount with value 250!\n";
} else {
    echo "WARNING: Could not find receipt_amount with value 250 in HTML.\n";
    // Search around ubl
    if (preg_match('/ubl.*?name="receipt_amount\[\]"[^>]*value="([^"]*)"/is', $html, $m2)) {
        echo "Found around ubl: value = '" . $m2[1] . "'\n";
    }
}
