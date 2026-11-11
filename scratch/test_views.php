<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

auth()->login(App\Models\User::first());

try {
    $instController = app(App\Http\Controllers\InstallmentController::class);
    
    // 1. Test installments.index
    $indexView = $instController->index(new \Illuminate\Http\Request());
    $indexHtml = $indexView->render();
    echo "1. Installments Index Render: OK (Bytes: " . strlen($indexHtml) . ")\n";
    if (strpos($indexHtml, 'New Installment Sale') !== false) {
        echo "   - 'New Installment Sale' button present: YES\n";
    }

    // 2. Test installments.monthly_report
    $reportView = $instController->monthlyReport(new \Illuminate\Http\Request());
    $reportHtml = $reportView->render();
    echo "2. Monthly Report Render: OK (Bytes: " . strlen($reportHtml) . ")\n";

    echo "\n=== BOTH INSTALLMENT PAGES RENDER WITH ZERO ERRORS ===\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
