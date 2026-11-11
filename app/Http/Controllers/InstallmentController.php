<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\SaleInstallmentPlan;
use App\Services\BalanceService;
use App\Services\JournalEntryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstallmentController extends Controller
{
    /**
     * Display a listing of installment plans.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $search = $request->get('search');
        $customerId = $request->get('customer_id');
        $durationMonths = $request->get('duration_months');
        $billNo = $request->get('bill_no') ?? $request->get('invoice_no');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = SaleInstallmentPlan::with(['sale', 'customer', 'installments'])
            ->latest('id');

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($customerId)) {
            $query->where('customer_id', $customerId);
        }

        if (!empty($durationMonths)) {
            $query->where('duration_months', (int) $durationMonths);
        }

        if (!empty($billNo)) {
            $query->whereHas('sale', function ($sq) use ($billNo) {
                $sq->where('invoice_no', 'like', "%{$billNo}%");
            });
        }

        if (!empty($fromDate)) {
            $query->whereDate('start_date', '>=', Carbon::parse($fromDate));
        }

        if (!empty($toDate)) {
            $query->whereDate('start_date', '<=', Carbon::parse($toDate));
        }

        if (!empty($customerId)) {
            $query->where('customer_id', $customerId);
        }

        if (!empty($billNo)) {
            $query->whereHas('sale', function ($sq) use ($billNo) {
                $sq->where('invoice_no', 'like', "%{$billNo}%");
            });
        }

        if (!empty($fromDate)) {
            $query->whereDate('due_date', '>=', Carbon::parse($fromDate));
        }

        if (!empty($toDate)) {
            $query->whereDate('due_date', '<=', Carbon::parse($toDate));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($cq) use ($search) {
                    $cq->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                })->orWhereHas('sale', function ($sq) use ($search) {
                    $sq->where('invoice_no', 'like', "%{$search}%");
                });
            });
        }

        $plans = $query->paginate(20)->withQueryString();

        // Statistics
        $totalPlansCount = SaleInstallmentPlan::count();
        $totalActivePlans = SaleInstallmentPlan::where('status', 'active')->count();
        $totalCompletedPlans = SaleInstallmentPlan::where('status', 'completed')->count();
        $totalFinanced = (float) SaleInstallmentPlan::sum('financed_amount');
        $totalCollected = (float) SaleInstallment::where('status', 'paid')->sum('paid_amount');
        $totalPending = (float) SaleInstallment::where('status', '!=', 'paid')->sum('amount');
        $overdueCount = SaleInstallment::where('status', '!=', 'paid')
            ->whereDate('due_date', '<', Carbon::today())
            ->count();

        $accounts = Account::where('status', 1)->orderBy('title')->get();
        $customers = Customer::orderBy('customer_name')->get();

        return view('admin_panel.sale.installments.index', compact(
            'plans',
            'status',
            'search',
            'customerId',
            'durationMonths',
            'billNo',
            'fromDate',
            'toDate',
            'totalPlansCount',
            'totalActivePlans',
            'totalCompletedPlans',
            'totalFinanced',
            'totalCollected',
            'totalPending',
            'overdueCount',
            'accounts',
            'customers'
        ));
    }

    /**
     * Get plan details and installment schedule via JSON for modal.
     */
    public function show($id)
    {
        $plan = SaleInstallmentPlan::with(['sale.items.product', 'customer', 'installments.receiptAccount'])->findOrFail($id);

        return response()->json([
            'ok' => true,
            'plan' => $plan,
        ]);
    }

    /**
     * Collect payment for a specific installment.
     */
    public function collectPayment(Request $request, $installmentId)
    {
        $request->validate([
            'paid_amount'        => 'required|numeric|min:0.01',
            'paid_date'          => 'required|date',
            'receipt_account_id' => 'required|exists:accounts,id',
            'payment_method'     => 'nullable|string',
            'notes'              => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $installmentId) {
            $installment = SaleInstallment::with(['plan', 'sale', 'customer'])->findOrFail($installmentId);
            $plan = $installment->plan;
            $customer = $installment->customer ?: ($plan->customer ?? null);

            $paidAmt = (float) $request->paid_amount;
            $paidDate = Carbon::parse($request->paid_date)->format('Y-m-d');

            $installment->paid_amount = $paidAmt;
            $installment->paid_date = $paidDate;
            $installment->receipt_account_id = $request->receipt_account_id;
            $installment->payment_method = $request->payment_method ?: 'Cash';
            $installment->notes = $request->notes;
            $installment->status = ($paidAmt >= (float) $installment->amount) ? 'paid' : 'partial';
            $installment->save();

            // Check if all installments in this plan are now paid
            $pendingCount = SaleInstallment::where('plan_id', $plan->id)
                ->where('status', '!=', 'paid')
                ->count();

            if ($pendingCount === 0) {
                $plan->status = 'completed';
                $plan->save();
            }

            // Post General Ledger / Journal Entry for this payment
            // Debit: Cash/Bank Account (Money received)
            // Credit: Accounts Receivable (Customer balance reduced)
            try {
                $journalService = app(JournalEntryService::class);
                $balanceService = app(BalanceService::class);

                if ($customer) {
                    $desc = "Installment #{$installment->installment_no}/{$plan->duration_months} Payment for Invoice #{$installment->sale->invoice_no}";
                    
                    // 1. Debit Cash / Bank Account
                    $journalService->recordEntry(
                        clone $installment->sale,
                        $request->receipt_account_id,
                        $paidAmt,
                        0,
                        $desc,
                        $paidDate
                    );

                    // 2. Credit Customer Receivable
                    $journalService->recordEntry(
                        clone $installment->sale,
                        $balanceService->getAccountsReceivableId(),
                        0,
                        $paidAmt,
                        $desc,
                        $paidDate,
                        $customer
                    );
                }
            } catch (\Exception $e) {
                \Log::error('Installment Payment GL Error: ' . $e->getMessage());
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => "Installment #{$installment->installment_no} collected successfully!",
                    'installment' => $installment,
                    'plan_status' => $plan->status,
                ]);
            }

            return redirect()->back()->with('success', "Installment #{$installment->installment_no} collected successfully!");
        });
    }

    /**
     * Preview calculation helper (for AJAX live preview if needed).
     */
    public function previewSchedule(Request $request)
    {
        $total = (float) $request->input('total_amount', 0);
        $advance = (float) $request->input('advance_amount', 0);
        $months = max(1, min(60, (int) $request->input('duration_months', 1)));
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->addMonth();

        $financed = max(0, $total - $advance);
        $monthly = $months > 0 ? round($financed / $months, 2) : 0;

        $schedule = [];
        $accumulated = 0;
        for ($i = 1; $i <= $months; $i++) {
            $dueDate = $startDate->copy()->addMonthsNoOverflow($i - 1)->format('d M, Y');
            if ($i === $months) {
                $amount = round($financed - $accumulated, 2);
            } else {
                $amount = $monthly;
                $accumulated += $amount;
            }

            $schedule[] = [
                'installment_no' => $i,
                'due_date' => $dueDate,
                'amount' => max(0, $amount),
            ];
        }

        return response()->json([
            'ok' => true,
            'total_amount' => $total,
            'advance_amount' => $advance,
            'financed_amount' => $financed,
            'duration_months' => $months,
            'monthly_installment' => $monthly,
            'schedule' => $schedule,
        ]);
    }

    /**
     * Display Monthly Installment Recovery Report.
     */
    public function monthlyReport(Request $request)
    {
        $selectedMonth = $request->get('month');
        $selectedYear = $request->get('year');
        $filter = $request->get('filter', 'all'); // 'all', 'due_soon', 'due_today', 'overdue', 'paid', 'pending'
        $search = $request->get('search');
        $customerId = $request->get('customer_id');
        $billNo = $request->get('bill_no') ?? $request->get('invoice_no');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $currMonth = (int) date('n');
        $currYear  = (int) date('Y');

        if (!$selectedMonth && !$selectedYear) {
            // Check if current month has any installment dues
            $hasCurrDues = SaleInstallment::whereBetween('due_date', [
                Carbon::create($currYear, $currMonth, 1)->startOfMonth()->startOfDay(),
                Carbon::create($currYear, $currMonth, 1)->endOfMonth()->endOfDay()
            ])->exists();

            if (!$hasCurrDues) {
                // Find next upcoming installment
                $nextInst = SaleInstallment::where('due_date', '>=', Carbon::today()->startOfDay())
                    ->orderBy('due_date', 'asc')
                    ->first();
                if ($nextInst) {
                    $nextCarbon = Carbon::parse($nextInst->due_date);
                    $month = $nextCarbon->month;
                    $year = $nextCarbon->year;
                } else {
                    $month = $currMonth;
                    $year = $currYear;
                }
            } else {
                $month = $currMonth;
                $year = $currYear;
            }
        } else {
            $month = $selectedMonth === 'all' ? 'all' : (int) $selectedMonth;
            $year = (int) ($selectedYear ?: date('Y'));
        }

        $today   = Carbon::today()->startOfDay();
        $twoDays = Carbon::today()->addDays(2)->endOfDay();

        $query = SaleInstallment::with(['plan', 'sale', 'customer', 'receiptAccount']);

        if ($month !== 'all') {
            $startDate = Carbon::create($year, (int)$month, 1)->startOfMonth()->startOfDay();
            $endDate   = Carbon::create($year, (int)$month, 1)->endOfMonth()->endOfDay();
            $query->whereBetween('due_date', [$startDate, $endDate]);
            $monthBase = SaleInstallment::whereBetween('due_date', [$startDate, $endDate]);
        } else {
            $monthBase = SaleInstallment::query();
        }

        if (!empty($customerId)) {
            $query->where('customer_id', $customerId);
        }

        if (!empty($billNo)) {
            $query->whereHas('sale', function ($sq) use ($billNo) {
                $sq->where('invoice_no', 'like', "%{$billNo}%");
            });
        }

        if (!empty($fromDate)) {
            $query->whereDate('due_date', '>=', Carbon::parse($fromDate));
        }

        if (!empty($toDate)) {
            $query->whereDate('due_date', '<=', Carbon::parse($toDate));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($cq) use ($search) {
                    $cq->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                })->orWhereHas('sale', function ($sq) use ($search) {
                    $sq->where('invoice_no', 'like', "%{$search}%");
                });
            });
        }

        // Apply filters
        if ($filter === 'due_soon') {
            $query->where('status', '!=', 'paid')
                  ->whereBetween('due_date', [$today, $twoDays]);
        } elseif ($filter === 'due_today') {
            $query->where('status', '!=', 'paid')
                  ->whereDate('due_date', Carbon::today()->format('Y-m-d'));
        } elseif ($filter === 'overdue') {
            $query->where('status', '!=', 'paid')
                  ->where('due_date', '<', $today);
        } elseif ($filter === 'paid') {
            $query->where('status', 'paid');
        } elseif ($filter === 'pending') {
            $query->where('status', '!=', 'paid');
        }

        $installments = $query->orderBy('due_date', 'asc')->paginate(25)->withQueryString();

        // Monthly KPI Summary Metrics (calculated for the selected scope)
        $totalExpected = (float) (clone $monthBase)->sum('amount');
        $totalCollected = (float) (clone $monthBase)->sum('paid_amount');
        $totalPending = max(0, $totalExpected - $totalCollected);

        $dueSoonCount = (clone $monthBase)->where('status', '!=', 'paid')
            ->whereBetween('due_date', [$today, $twoDays])
            ->count();

        $dueTodayCount = (clone $monthBase)->where('status', '!=', 'paid')
            ->whereDate('due_date', Carbon::today()->format('Y-m-d'))
            ->count();

        $overdueCount = (clone $monthBase)->where('status', '!=', 'paid')
            ->where('due_date', '<', $today)
            ->count();

        // Upcoming hint if selected month is empty
        $upcomingHint = null;
        if ($month !== 'all' && $installments->total() === 0) {
            $nearest = SaleInstallment::where('due_date', '>=', Carbon::today()->startOfDay())
                ->orderBy('due_date', 'asc')
                ->first();
            if ($nearest) {
                $nDate = Carbon::parse($nearest->due_date);
                $upcomingHint = [
                    'month' => $nDate->month,
                    'year'  => $nDate->year,
                    'month_name' => $nDate->format('F Y'),
                ];
            }
        }

        $accounts = Account::where('status', 1)->orderBy('title')->get();
        $customers = Customer::orderBy('customer_name')->get();

        return view('admin_panel.sale.installments.monthly_report', compact(
            'installments',
            'year',
            'month',
            'filter',
            'search',
            'customerId',
            'billNo',
            'fromDate',
            'toDate',
            'totalExpected',
            'totalCollected',
            'totalPending',
            'dueSoonCount',
            'dueTodayCount',
            'overdueCount',
            'upcomingHint',
            'accounts',
            'customers'
        ));
    }

    /**
     * Get real-time Due Alerts (2-Day Advance, Due Today, Overdue) in English.
     */
    public function getDueAlerts()
    {
        $today = Carbon::today()->format('Y-m-d');
        $twoDays = Carbon::today()->addDays(2)->format('Y-m-d');

        // 1. Due in 0 to 2 days
        $upcoming = SaleInstallment::with(['customer', 'sale', 'plan'])
            ->where('status', '!=', 'paid')
            ->whereBetween('due_date', [$today, $twoDays])
            ->orderBy('due_date', 'asc')
            ->get();

        // 2. Overdue
        $overdue = SaleInstallment::with(['customer', 'sale', 'plan'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $today)
            ->orderBy('due_date', 'asc')
            ->take(30)
            ->get();

        $formattedAlerts = [];

        foreach ($upcoming as $inst) {
            $custName = $inst->customer ? $inst->customer->customer_name : 'Walk-in Customer';
            $custMobile = $inst->customer ? ($inst->customer->mobile ?: '') : '';
            $invoiceNo = $inst->sale ? $inst->sale->invoice_no : 'INV';
            $remaining = max(0, (float)$inst->amount - (float)$inst->paid_amount);
            $totalMonths = $inst->plan ? $inst->plan->duration_months : 12;

            if ($inst->due_date == $today) {
                $urgency = 'critical';
                $badgeText = 'Due Today';
                $title = "Installment Due Today: {$custName}";
                $message = "Installment #{$inst->installment_no} of {$totalMonths} (Rs. " . number_format($remaining, 2) . ") for Invoice #{$invoiceNo} is due today.";
            } else {
                $diffDays = Carbon::today()->diffInDays(Carbon::parse($inst->due_date));
                $urgency = 'upcoming';
                $badgeText = "Due in {$diffDays} " . ($diffDays == 1 ? 'Day' : 'Days');
                $title = "Installment Due Soon: {$custName}";
                $message = "Installment #{$inst->installment_no} of {$totalMonths} (Rs. " . number_format($remaining, 2) . ") for Invoice #{$invoiceNo} is due on " . Carbon::parse($inst->due_date)->format('d-M-Y') . " ({$badgeText}).";
            }

            $formattedAlerts[] = [
                'id' => $inst->id,
                'plan_id' => $inst->plan_id,
                'installment_no' => $inst->installment_no,
                'customer_name' => $custName,
                'customer_mobile' => $custMobile,
                'invoice_no' => $invoiceNo,
                'due_date' => $inst->due_date,
                'formatted_due_date' => Carbon::parse($inst->due_date)->format('d-M-Y'),
                'amount' => (float)$inst->amount,
                'remaining' => $remaining,
                'title' => $title,
                'message' => $message,
                'badge' => $badgeText,
                'urgency' => $urgency,
                'action_url' => route('installments.monthly_report', ['search' => $invoiceNo]),
            ];
        }

        foreach ($overdue as $inst) {
            $custName = $inst->customer ? $inst->customer->customer_name : 'Walk-in Customer';
            $custMobile = $inst->customer ? ($inst->customer->mobile ?: '') : '';
            $invoiceNo = $inst->sale ? $inst->sale->invoice_no : 'INV';
            $remaining = max(0, (float)$inst->amount - (float)$inst->paid_amount);
            $totalMonths = $inst->plan ? $inst->plan->duration_months : 12;
            $overdueDays = Carbon::parse($inst->due_date)->diffInDays(Carbon::today());

            $formattedAlerts[] = [
                'id' => $inst->id,
                'plan_id' => $inst->plan_id,
                'installment_no' => $inst->installment_no,
                'customer_name' => $custName,
                'customer_mobile' => $custMobile,
                'invoice_no' => $invoiceNo,
                'due_date' => $inst->due_date,
                'formatted_due_date' => Carbon::parse($inst->due_date)->format('d-M-Y'),
                'amount' => (float)$inst->amount,
                'remaining' => $remaining,
                'title' => "Overdue Installment: {$custName}",
                'message' => "Installment #{$inst->installment_no} of {$totalMonths} (Rs. " . number_format($remaining, 2) . ") for Invoice #{$invoiceNo} is {$overdueDays} days overdue (Due Date: " . Carbon::parse($inst->due_date)->format('d-M-Y') . ").",
                'badge' => "Overdue ({$overdueDays}d)",
                'urgency' => 'overdue',
                'action_url' => route('installments.monthly_report', ['search' => $invoiceNo]),
            ];
        }

        $nextScheduled = SaleInstallment::with(['customer', 'sale', 'plan'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '>=', $today)
            ->orderBy('due_date', 'asc')
            ->first();

        $nextScheduledData = null;
        if ($nextScheduled) {
            $daysDiff = Carbon::today()->diffInDays(Carbon::parse($nextScheduled->due_date));
            $custName = $nextScheduled->customer ? $nextScheduled->customer->customer_name : 'Customer';
            $nextScheduledData = [
                'id' => $nextScheduled->id,
                'customer_name' => $custName,
                'invoice_no' => $nextScheduled->sale ? $nextScheduled->sale->invoice_no : 'INV',
                'amount' => (float)$nextScheduled->amount,
                'due_date' => Carbon::parse($nextScheduled->due_date)->format('d-M-Y'),
                'installment_no' => $nextScheduled->installment_no,
                'total_months' => $nextScheduled->plan ? $nextScheduled->plan->duration_months : 12,
                'days_remaining' => $daysDiff,
            ];
        }

        return response()->json([
            'ok' => true,
            'total_alerts' => count($formattedAlerts),
            'upcoming_count' => $upcoming->count(),
            'overdue_count' => $overdue->count(),
            'alerts' => $formattedAlerts,
            'next_scheduled' => $nextScheduledData,
        ]);
    }
}
