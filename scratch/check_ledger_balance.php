<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$accounts = App\Models\Account::where('current_balance', '!=', 0)
    ->orWhereHas('head', function($q) { $q->whereIn('name', ['Cash', 'Bank']); })
    ->get();

foreach ($accounts as $a) {
    $debits = App\Models\JournalEntry::where('account_id', $a->id)->sum('debit');
    $credits = App\Models\JournalEntry::where('account_id', $a->id)->sum('credit');
    $calculated = ($a->opening_balance ?? 0) + $debits - $credits;
    echo "ID: {$a->id} | {$a->title} ({$a->account_code}) | DB current_balance: {$a->current_balance} | Opening: {$a->opening_balance} | Sum Debits: {$debits} | Sum Credits: {$credits} | Calculated (Opening+Dr-Cr): {$calculated}\n";
}
