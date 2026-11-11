<?php

$path = __DIR__ . '/../app/Http/Controllers/InstallmentController.php';
$content = file_get_contents($path);
$content = str_replace("\r\n", "\n", $content);

// Replace index method
$oldIndexTarget = '    public function index(Request $request)
    {
        $status = $request->get(\'status\', \'all\');
        $search = $request->get(\'search\');

        $query = SaleInstallmentPlan::with([\'sale\', \'customer\', \'installments\'])
            ->latest(\'id\');

        if ($status !== \'all\') {
            $query->where(\'status\', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas(\'customer\', function ($cq) use ($search) {
                    $cq->where(\'customer_name\', \'like\', "%{$search}%")
                        ->orWhere(\'mobile\', \'like\', "%{$search}%");
                })->orWhereHas(\'sale\', function ($sq) use ($search) {
                    $sq->where(\'invoice_no\', \'like\', "%{$search}%");
                });
            });
        }

        $plans = $query->paginate(20)->withQueryString();

        // Statistics
        $totalActivePlans = SaleInstallmentPlan::where(\'status\', \'active\')->count();
        $totalFinanced = (float) SaleInstallmentPlan::where(\'status\', \'active\')->sum(\'financed_amount\');
        $totalCollected = (float) SaleInstallment::where(\'status\', \'paid\')->sum(\'paid_amount\');
        $totalPending = (float) SaleInstallment::where(\'status\', \'!=\', \'paid\')->sum(\'amount\');
        $overdueCount = SaleInstallment::where(\'status\', \'!=\', \'paid\')
            ->whereDate(\'due_date\', \'<\', Carbon::today())
            ->count();

        $accounts = Account::where(\'status\', 1)->orderBy(\'title\')->get();

        return view(\'admin_panel.sale.installments.index\', compact(
            \'plans\',
            \'status\',
            \'search\',
            \'totalActivePlans\',
            \'totalFinanced\',
            \'totalCollected\',
            \'totalPending\',
            \'overdueCount\',
            \'accounts\'
        ));
    }';

$newIndex = '    public function index(Request $request)
    {
        $status = $request->get(\'status\', \'all\');
        $search = $request->get(\'search\');
        $customerId = $request->get(\'customer_id\');
        $durationMonths = $request->get(\'duration_months\');
        $billNo = $request->get(\'bill_no\') ?? $request->get(\'invoice_no\');
        $fromDate = $request->get(\'from_date\');
        $toDate = $request->get(\'to_date\');

        $query = SaleInstallmentPlan::with([\'sale\', \'customer\', \'installments\'])
            ->latest(\'id\');

        if ($status !== \'all\' && !empty($status)) {
            $query->where(\'status\', $status);
        }

        if (!empty($customerId)) {
            $query->where(\'customer_id\', $customerId);
        }

        if (!empty($durationMonths)) {
            $query->where(\'duration_months\', (int) $durationMonths);
        }

        if (!empty($billNo)) {
            $query->whereHas(\'sale\', function ($sq) use ($billNo) {
                $sq->where(\'invoice_no\', \'like\', "%{$billNo}%");
            });
        }

        if (!empty($fromDate)) {
            $query->whereDate(\'start_date\', \'>=\', Carbon::parse($fromDate));
        }

        if (!empty($toDate)) {
            $query->whereDate(\'start_date\', \'<=\', Carbon::parse($toDate));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas(\'customer\', function ($cq) use ($search) {
                    $cq->where(\'customer_name\', \'like\', "%{$search}%")
                        ->orWhere(\'mobile\', \'like\', "%{$search}%");
                })->orWhereHas(\'sale\', function ($sq) use ($search) {
                    $sq->where(\'invoice_no\', \'like\', "%{$search}%");
                });
            });
        }

        $plans = $query->paginate(20)->withQueryString();

        // Statistics
        $totalPlansCount = SaleInstallmentPlan::count();
        $totalActivePlans = SaleInstallmentPlan::where(\'status\', \'active\')->count();
        $totalCompletedPlans = SaleInstallmentPlan::where(\'status\', \'completed\')->count();
        $totalFinanced = (float) SaleInstallmentPlan::sum(\'financed_amount\');
        $totalCollected = (float) SaleInstallment::where(\'status\', \'paid\')->sum(\'paid_amount\');
        $totalPending = (float) SaleInstallment::where(\'status\', \'!=\', \'paid\')->sum(\'amount\');
        $overdueCount = SaleInstallment::where(\'status\', \'!=\', \'paid\')
            ->whereDate(\'due_date\', \'<\', Carbon::today())
            ->count();

        $accounts = Account::where(\'status\', 1)->orderBy(\'title\')->get();
        $customers = Customer::orderBy(\'customer_name\')->get();

        return view(\'admin_panel.sale.installments.index\', compact(
            \'plans\',
            \'status\',
            \'search\',
            \'customerId\',
            \'durationMonths\',
            \'billNo\',
            \'fromDate\',
            \'toDate\',
            \'totalPlansCount\',
            \'totalActivePlans\',
            \'totalCompletedPlans\',
            \'totalFinanced\',
            \'totalCollected\',
            \'totalPending\',
            \'overdueCount\',
            \'accounts\',
            \'customers\'
        ));
    }';

if (strpos($content, $oldIndexTarget) !== false) {
    $content = str_replace($oldIndexTarget, $newIndex, $content);
    echo "index method updated\n";
} else {
    echo "index method NOT matched\n";
}

// In monthlyReport, add $customerId, $billNo, $fromDate, $toDate, and pass $customers
$oldMrSignature = '        $selectedMonth = $request->get(\'month\');
        $selectedYear = $request->get(\'year\');
        $filter = $request->get(\'filter\', \'all\'); // \'all\', \'due_soon\', \'due_today\', \'overdue\', \'paid\', \'pending\'
        $search = $request->get(\'search\');';

$newMrSignature = '        $selectedMonth = $request->get(\'month\');
        $selectedYear = $request->get(\'year\');
        $filter = $request->get(\'filter\', \'all\'); // \'all\', \'due_soon\', \'due_today\', \'overdue\', \'paid\', \'pending\'
        $search = $request->get(\'search\');
        $customerId = $request->get(\'customer_id\');
        $billNo = $request->get(\'bill_no\') ?? $request->get(\'invoice_no\');
        $fromDate = $request->get(\'from_date\');
        $toDate = $request->get(\'to_date\');';

if (strpos($content, $oldMrSignature) !== false) {
    $content = str_replace($oldMrSignature, $newMrSignature, $content);
    echo "monthlyReport signature updated\n";
} else {
    echo "monthlyReport signature NOT matched\n";
}

$oldSearchBlock = '        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas(\'customer\', function ($cq) use ($search) {
                    $cq->where(\'customer_name\', \'like\', "%{$search}%")
                        ->orWhere(\'mobile\', \'like\', "%{$search}%");
                })->orWhereHas(\'sale\', function ($sq) use ($search) {
                    $sq->where(\'invoice_no\', \'like\', "%{$search}%");
                });
            });
        }';

$newSearchBlock = '        if (!empty($customerId)) {
            $query->where(\'customer_id\', $customerId);
        }

        if (!empty($billNo)) {
            $query->whereHas(\'sale\', function ($sq) use ($billNo) {
                $sq->where(\'invoice_no\', \'like\', "%{$billNo}%");
            });
        }

        if (!empty($fromDate)) {
            $query->whereDate(\'due_date\', \'>=\', Carbon::parse($fromDate));
        }

        if (!empty($toDate)) {
            $query->whereDate(\'due_date\', \'<=\', Carbon::parse($toDate));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas(\'customer\', function ($cq) use ($search) {
                    $cq->where(\'customer_name\', \'like\', "%{$search}%")
                        ->orWhere(\'mobile\', \'like\', "%{$search}%");
                })->orWhereHas(\'sale\', function ($sq) use ($search) {
                    $sq->where(\'invoice_no\', \'like\', "%{$search}%");
                });
            });
        }';

if (strpos($content, $oldSearchBlock) !== false) {
    $content = str_replace($oldSearchBlock, $newSearchBlock, $content);
    echo "monthlyReport search block updated\n";
} else {
    echo "monthlyReport search block NOT matched\n";
}

// In monthlyReport, compact $customers, $customerId, $billNo, $fromDate, $toDate
$oldCompact = '        return view(\'admin_panel.sale.installments.monthly_report\', compact(
            \'installments\',
            \'month\',
            \'year\',
            \'filter\',
            \'search\',
            \'totalExpected\',
            \'totalCollected\',
            \'totalPending\',
            \'dueSoonCount\',
            \'dueTodayCount\',
            \'overdueCount\',
            \'upcomingHint\',
            \'accounts\'
        ));';

$newCompact = '        $customers = Customer::orderBy(\'customer_name\')->get();

        return view(\'admin_panel.sale.installments.monthly_report\', compact(
            \'installments\',
            \'month\',
            \'year\',
            \'filter\',
            \'search\',
            \'customerId\',
            \'billNo\',
            \'fromDate\',
            \'toDate\',
            \'totalExpected\',
            \'totalCollected\',
            \'totalPending\',
            \'dueSoonCount\',
            \'dueTodayCount\',
            \'overdueCount\',
            \'upcomingHint\',
            \'accounts\',
            \'customers\'
        ));';

if (strpos($content, $oldCompact) !== false) {
    $content = str_replace($oldCompact, $newCompact, $content);
    echo "monthlyReport compact updated\n";
} else {
    echo "monthlyReport compact NOT matched\n";
}

file_put_contents($path, $content);
echo "InstallmentController update finished.\n";
