<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Today is: " . \Carbon\Carbon::today()->format('Y-m-d') . "\n\n";

$insts = \App\Models\SaleInstallment::with(['customer', 'sale'])->get();
foreach ($insts as $i) {
    echo "Inst #{$i->installment_no} | Plan #{$i->plan_id} | Sale: " . ($i->sale->invoice_no ?? 'N/A') . " | Cust: " . ($i->customer->customer_name ?? 'N/A') . " | Due: {$i->due_date} | Amt: {$i->amount} | Status: {$i->status}\n";
}
