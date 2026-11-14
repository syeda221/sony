<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = App\Models\JournalEntry::where('source_id', 10)->get();
echo "Entries for source_id 10: " . $entries->count() . "\n";
foreach ($entries as $e) {
    echo "ID: {$e->id}, Source: {$e->source_type}, Acc: {$e->account_id} (" . ($e->account->title ?? '') . "), Dr: {$e->debit}, Cr: {$e->credit}, Desc: {$e->description}\n";
}

$rvs = App\Models\ReceiptsVoucher::where('remarks', 'like', '%10%')->orWhere('remarks', 'like', '%INV%')->take(5)->get();
echo "\nRVs: " . $rvs->count() . "\n";
foreach ($rvs as $r) {
    echo "RV: {$r->rvid}, Party: {$r->party_id}, Total: {$r->total_amount}, Rows: {$r->row_account_id}, Amts: {$r->row_amount}, Remarks: {$r->remarks}\n";
}
