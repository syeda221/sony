@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* Exact Sales Management UI Language */
        .sale-stat-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease-in-out;
            height: 100%;
        }
        .sale-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .sale-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        /* Responsive Status Filter Pills */
        .sales-status-pills {
            display: flex !important;
            align-items: center;
            gap: 8px;
            overflow-x: auto !important;
            white-space: nowrap !important;
            padding-bottom: 6px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .sales-status-pills::-webkit-scrollbar {
            height: 4px;
        }
        .sales-status-pills::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .sales-status-pills .btn {
            flex-shrink: 0 !important;
        }

        /* Clean & Bold Filter Panel */
        .filter-panel {
            background-color: #f8fafc !important;
            border: 2px dashed #cbd5e1 !important;
            border-radius: 12px !important;
            padding: 16px !important;
        }
        
        .filter-panel label {
            font-size: 11px;
            font-weight: 700 !important;
            color: #475569 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-panel .form-control,
        .filter-panel .form-select {
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-weight: 500 !important;
            color: #1e293b !important;
            transition: all 0.2s ease-in-out;
            height: 38px !important;
            font-size: 13px !important;
        }
        
        .filter-panel .form-control:focus,
        .filter-panel .form-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }

        /* Select2 In Filter Panel */
        .filter-panel .select2-container {
            width: 100% !important;
        }
        .filter-panel .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 5px 8px !important;
            background-color: #ffffff !important;
        }
        .filter-panel .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px !important;
            color: #1e293b !important;
            font-weight: 500 !important;
            font-size: 13px !important;
            padding-left: 0 !important;
        }
        .filter-panel .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 6px !important;
        }

        /* Premium Buttons */
        .btn-premium-primary {
            background-color: #2563eb !important;
            border: 1.5px solid #1d4ed8 !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            height: 38px !important;
            padding: 0 16px !important;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-premium-primary:hover {
            background-color: #1d4ed8 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
        }
        
        .btn-premium-secondary {
            background-color: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #475569 !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            height: 38px !important;
            padding: 0 16px !important;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .btn-premium-secondary:hover {
            background-color: #f1f5f9 !important;
            border-color: #94a3b8 !important;
            color: #1e293b !important;
        }

        /* Premium Table */
        .premium-card {
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
            background-color: #ffffff;
        }

        .premium-table {
            border: 2px solid #475569 !important;
            border-radius: 8px !important;
            overflow: visible !important;
        }

        .premium-table thead th {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #475569 !important;
            border-right: 1.5px solid #cbd5e1 !important;
            padding: 12px 10px !important;
            white-space: nowrap;
        }
        
        .premium-table tbody td {
            border: 1.5px solid #e2e8f0 !important;
            padding: 10px 10px !important;
            font-size: 13px !important;
            color: #334155 !important;
            background-color: #ffffff;
        }
        
        .premium-table tbody tr:hover td {
            background-color: #f8fafc !important;
        }

        /* Alert Badges */
        .badge-due-soon {
            background-color: #fef08a !important;
            color: #854d0e !important;
            border: 1px solid #facc15 !important;
        }
        .badge-due-today {
            background-color: #fed7aa !important;
            color: #9a3412 !important;
            border: 1px solid #fb923c !important;
            animation: pulse-orange 2s infinite;
        }
        .badge-overdue {
            background-color: #fecaca !important;
            color: #991b1b !important;
            border: 1px solid #f87171 !important;
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        @keyframes pulse-orange {
            0% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(249, 115, 22, 0); }
            100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0); }
        }

        @media print {
            .no-print, .sales-hdr-actions, .filter-panel, .sales-status-pills, .btn {
                display: none !important;
            }
            .page-content-wrapper {
                padding: 0 !important;
            }
            .premium-card {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>

    <div class="page-content-wrapper p-3 p-md-4">
        {{-- Page Header --}}
        <div class="row align-items-center justify-content-between mb-4">
            <div class="col-12 col-md-auto mb-3 mb-md-0">
                <h3 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="fas fa-file-invoice-dollar text-primary"></i> Monthly Installment Recovery Report
                </h3>
                <p class="text-muted small mb-0">Track monthly collection targets, upcoming customer dues, and overdue installments</p>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2 sales-hdr-actions no-print">
                <a href="{{ route('installments.index') }}" class="btn btn-premium-secondary px-3 shadow-sm">
                    <i class="fas fa-arrow-left me-1"></i> Plans List
                </a>
                <button type="button" onclick="window.print()" class="btn btn-premium-secondary px-3 shadow-sm">
                    <i class="fas fa-print me-1 text-secondary"></i> Print Report
                </button>
                <a href="{{ route('sale.add') }}" class="btn btn-premium-primary px-3 shadow-sm">
                    <i class="fas fa-plus me-1"></i> New Installment Sale
                </a>
            </div>
        </div>

        {{-- Smart Upcoming Month Hint (If current month has 0 dues) --}}
        @if($upcomingHint)
            <div class="alert alert-info border-info shadow-sm rounded-3 p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print" style="background-color: #eff6ff; border: 1.5px solid #60a5fa;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-info-circle text-primary fs-5"></i>
                    <div>
                        <strong class="text-dark">No Installment Dues in {{ $month === 'all' ? 'this filter' : date('F Y', mktime(0, 0, 0, (int)$month, 1, $year)) }}</strong>
                        <div class="small text-secondary">Nearest upcoming customer dues start in <strong>{{ $upcomingHint['month_name'] }}</strong>.</div>
                    </div>
                </div>
                <a href="{{ route('installments.monthly_report', ['month' => $upcomingHint['month'], 'year' => $upcomingHint['year']]) }}" class="btn btn-sm btn-primary fw-bold px-3">
                    <i class="fas fa-arrow-right me-1"></i> Switch to {{ $upcomingHint['month_name'] }}
                </a>
            </div>
        @endif

        {{-- Monthly KPI Stat Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl">
                <div class="sale-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Monthly Target</div>
                            <h4 class="fw-bold text-dark mb-0 mt-1" style="font-size: 1.15rem;">Rs. {{ number_format($totalExpected, 2) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #eff6ff; color: #2563eb;">
                            <i class="fas fa-bullseye"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="sale-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Collected</div>
                            <h4 class="fw-bold text-success mb-0 mt-1" style="font-size: 1.15rem;">Rs. {{ number_format($totalCollected, 2) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #ecfdf5; color: #059669;">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="sale-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Pending Dues</div>
                            <h4 class="fw-bold text-warning mb-0 mt-1" style="font-size: 1.15rem; color: #d97706 !important;">Rs. {{ number_format($totalPending, 2) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #fffbeb; color: #d97706;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-6 col-xl">
                <div class="sale-stat-card" style="{{ $dueSoonCount > 0 ? 'border: 1.5px solid #facc15; background: #fefce8;' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Due in 2 Days</div>
                            <h4 class="fw-bold text-dark mb-0 mt-1" style="font-size: 1.15rem;">{{ number_format($dueSoonCount) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #fef08a; color: #854d0e;">
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
                <div class="sale-stat-card" style="{{ $overdueCount > 0 ? 'border: 1.5px solid #f87171; background: #fef2f2;' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Overdue Dues</div>
                            <h4 class="fw-bold text-danger mb-0 mt-1" style="font-size: 1.15rem;">{{ number_format($overdueCount) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #fee2e2; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Filter Pills --}}
        <div class="mb-4 sales-status-pills no-print">
            <a href="{{ route('installments.monthly_report', ['month' => $month, 'year' => $year, 'filter' => 'all', 'search' => $search, 'customer_id' => $customerId]) }}" 
               class="btn btn-sm {{ $filter === 'all' || !$filter ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-3 shadow-sm px-3 fw-bold">
                All Dues <span class="badge bg-white text-dark ms-1">{{ $installments->total() }}</span>
            </a>
            <a href="{{ route('installments.monthly_report', ['month' => $month, 'year' => $year, 'filter' => 'due_soon', 'search' => $search, 'customer_id' => $customerId]) }}" 
               class="btn btn-sm {{ $filter === 'due_soon' ? 'btn-warning text-dark' : 'btn-outline-warning' }} rounded-3 shadow-sm px-3 fw-bold">
                <i class="fas fa-bell me-1"></i> Due in 2 Days
                @if($dueSoonCount > 0)
                    <span class="badge bg-dark text-white ms-1">{{ $dueSoonCount }}</span>
                @endif
            </a>
            <a href="{{ route('installments.monthly_report', ['month' => $month, 'year' => $year, 'filter' => 'due_today', 'search' => $search, 'customer_id' => $customerId]) }}" 
               class="btn btn-sm {{ $filter === 'due_today' ? 'btn-danger text-white' : 'btn-outline-danger' }} rounded-3 shadow-sm px-3 fw-bold">
                <i class="fas fa-exclamation-circle me-1"></i> Due Today
                @if($dueTodayCount > 0)
                    <span class="badge bg-white text-danger ms-1">{{ $dueTodayCount }}</span>
                @endif
            </a>
            <a href="{{ route('installments.monthly_report', ['month' => $month, 'year' => $year, 'filter' => 'overdue', 'search' => $search, 'customer_id' => $customerId]) }}" 
               class="btn btn-sm {{ $filter === 'overdue' ? 'btn-danger text-white' : 'btn-outline-danger' }} rounded-3 shadow-sm px-3 fw-bold">
                <i class="fas fa-exclamation-triangle me-1"></i> Overdue
                @if($overdueCount > 0)
                    <span class="badge bg-white text-danger ms-1">{{ $overdueCount }}</span>
                @endif
            </a>
            <a href="{{ route('installments.monthly_report', ['month' => $month, 'year' => $year, 'filter' => 'pending', 'search' => $search, 'customer_id' => $customerId]) }}" 
               class="btn btn-sm {{ $filter === 'pending' ? 'btn-primary text-white' : 'btn-outline-primary' }} rounded-3 shadow-sm px-3 fw-bold">
                <i class="fas fa-hourglass-half me-1"></i> Pending Dues
            </a>
            <a href="{{ route('installments.monthly_report', ['month' => $month, 'year' => $year, 'filter' => 'paid', 'search' => $search, 'customer_id' => $customerId]) }}" 
               class="btn btn-sm {{ $filter === 'paid' ? 'btn-success text-white' : 'btn-outline-success' }} rounded-3 shadow-sm px-3 fw-bold">
                <i class="fas fa-check-circle me-1"></i> Fully Paid
            </a>
        </div>

        {{-- Main Table Container Card --}}
        <div class="card premium-card">
            <div class="card-body p-3 p-md-4">
                {{-- Clean & Bold Filter Panel (Exact Sale Page Pattern) --}}
                <div class="filter-panel mb-4 no-print">
                    <form method="GET" action="{{ route('installments.monthly_report') }}" id="reportFilterForm" class="row g-2 align-items-end">
                        <input type="hidden" name="filter" value="{{ $filter }}">

                        {{-- Month Selector --}}
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">Month</label>
                            <select name="month" class="form-select fw-semibold">
                                <option value="all" {{ $month === 'all' ? 'selected' : '' }}>All Months (All Dues)</option>
                                @for($m = 1; $m <= 12; $m++)
                                    @php
                                        $mName = date('F', mktime(0, 0, 0, $m, 1));
                                    @endphp
                                    <option value="{{ $m }}" {{ ($month !== 'all' && (int)$month === $m) ? 'selected' : '' }}>
                                        {{ $mName }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        {{-- Year Selector --}}
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">Year</label>
                            <select name="year" class="form-select fw-semibold">
                                @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        {{-- Customer Dropdown --}}
                        <div class="col-12 col-md-3">
                            <label class="form-label mb-1">Customer</label>
                            <select class="form-select select2-customer" name="customer_id" id="filter_customer_id" style="width: 100%;">
                                <option value="">All Customers</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>
                                        {{ $c->customer_name }} {{ $c->mobile ? '('.$c->mobile.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Invoice / Bill # --}}
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">Invoice / Bill#</label>
                            <input type="text" class="form-control" name="bill_no" value="{{ $billNo }}" placeholder="e.g. INV-0001">
                        </div>

                        {{-- Search Keyword --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label mb-1">Customer Name / Mobile</label>
                            <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="Search customer...">
                        </div>

                        {{-- Action Buttons --}}
                        <div class="col-12 d-flex justify-content-end gap-2 mt-2 filter-actions-row">
                            <a href="{{ route('installments.monthly_report') }}" class="btn btn-premium-secondary px-3" id="btnReset">
                                <i class="fas fa-undo me-1"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-premium-primary px-4" id="btnSearch">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Table Header Info --}}
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="fw-bold text-dark" style="font-size: 13.5px;">
                        <i class="fas fa-list-check me-1 text-primary"></i>
                        Customer Dues for {{ $month === 'all' ? 'All Months' : date('F Y', mktime(0, 0, 0, (int)$month, 1, $year)) }}
                    </span>
                    <span class="text-muted small">Showing {{ $installments->total() }} records</span>
                </div>

                {{-- Report Table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle premium-table mb-0" style="width: 100%;">
                        <thead>
                            <tr>
                                <th class="ps-3 py-3">Customer Information</th>
                                <th class="py-3">Invoice #</th>
                                <th class="py-3 text-center">Installment #</th>
                                <th class="py-3">Due Date</th>
                                <th class="py-3 text-end">Due Amount</th>
                                <th class="py-3 text-end">Paid Amount</th>
                                <th class="py-3 text-end">Remaining Due</th>
                                <th class="py-3 text-center">Alert / Status</th>
                                <th class="pe-3 py-3 text-end no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installments as $inst)
                                @php
                                    $todayDate = \Carbon\Carbon::today();
                                    $dueDate = \Carbon\Carbon::parse($inst->due_date);
                                    $isPaid = $inst->status === 'paid';
                                    $remainingAmt = max(0, (float)$inst->amount - (float)$inst->paid_amount);
                                    $totalPlanMonths = $inst->plan ? $inst->plan->duration_months : 12;

                                    // Alert Classification (in English)
                                    $alertBadge = '';
                                    
                                    if ($isPaid) {
                                        $alertBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check me-1"></i> Paid</span>';
                                    } elseif ($dueDate->isPast() && !$dueDate->isToday()) {
                                        $overdueDays = $dueDate->diffInDays($todayDate);
                                        $alertBadge = '<span class="badge badge-overdue px-2 py-1"><i class="fas fa-exclamation-triangle me-1"></i> Overdue (' . $overdueDays . 'd)</span>';
                                    } elseif ($dueDate->isToday()) {
                                        $alertBadge = '<span class="badge badge-due-today px-2 py-1 fw-bold"><i class="fas fa-bell me-1"></i> Due Today</span>';
                                    } elseif ($dueDate->diffInDays($todayDate) <= 2) {
                                        $alertBadge = '<span class="badge badge-due-soon px-2 py-1 fw-bold"><i class="fas fa-clock me-1"></i> Due in 2 Days</span>';
                                    } else {
                                        $alertBadge = '<span class="badge bg-light text-secondary border px-2 py-1">Upcoming</span>';
                                    }

                                    // Clean Mobile for WhatsApp link
                                    $rawMobile = $inst->customer ? preg_replace('/[^0-9]/', '', $inst->customer->mobile) : '';
                                    if (str_starts_with($rawMobile, '03')) {
                                        $rawMobile = '92' . substr($rawMobile, 1);
                                    }
                                    $companyName = \App\Models\Setting::get('company_name', 'Prowave Technologies');
                                    $custName = $inst->customer ? $inst->customer->customer_name : 'Valued Customer';
                                    $invNo = $inst->sale ? $inst->sale->invoice_no : '';
                                    
                                    $whatsappMessage = urlencode("Payment Reminder:\nDear {$custName},\nYour Installment #{$inst->installment_no} of Rs. " . number_format($remainingAmt, 2) . " for Invoice #{$invNo} is due on " . $dueDate->format('d-M-Y') . ".\nPlease ensure timely payment.\nThank you,\n{$companyName}");
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $custName }}</div>
                                        @if(!empty($inst->customer && $inst->customer->mobile))
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <small class="text-muted"><i class="fas fa-phone-alt me-1"></i>{{ $inst->customer->mobile }}</small>
                                                @if(!empty($rawMobile))
                                                    <a href="https://wa.me/{{ $rawMobile }}?text={{ $whatsappMessage }}" target="_blank" class="badge bg-success-subtle text-success text-decoration-none no-print shadow-sm" title="Send WhatsApp Reminder">
                                                        <i class="fab fa-whatsapp me-1"></i> WhatsApp
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inst->sale)
                                            <a href="{{ route('sales.invoice', $inst->sale->id) }}" class="fw-bold text-primary text-decoration-none" target="_blank" title="View Sale Invoice">
                                                <i class="fas fa-file-invoice me-1"></i>{{ $inst->sale->invoice_no }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                            #{{ $inst->installment_no }} of {{ $totalPlanMonths }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $dueDate->format('d-M-Y') }}
                                        </div>
                                        <small class="text-muted">{{ $dueDate->format('l') }}</small>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        Rs. {{ number_format($inst->amount, 2) }}
                                    </td>
                                    <td class="text-end fw-semibold text-success">
                                        Rs. {{ number_format($inst->paid_amount, 2) }}
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        Rs. {{ number_format($remainingAmt, 2) }}
                                    </td>
                                    <td class="text-center">
                                        {!! $alertBadge !!}
                                    </td>
                                    <td class="pe-3 text-end no-print">
                                        @if(!$isPaid)
                                            <button type="button" class="btn btn-sm btn-success btn-collect-inst shadow-sm fw-semibold"
                                                    data-inst-id="{{ $inst->id }}"
                                                    data-plan-id="{{ $inst->plan_id }}"
                                                    data-inst-no="{{ $inst->installment_no }}"
                                                    data-total-months="{{ $totalPlanMonths }}"
                                                    data-due-date="{{ $inst->due_date }}"
                                                    data-due-amount="{{ $inst->amount }}"
                                                    data-paid-amount="{{ $inst->paid_amount }}">
                                                <i class="fas fa-cash-register me-1"></i> Collect
                                            </button>
                                        @else
                                            <span class="text-success small fw-bold">
                                                <i class="fas fa-check-circle me-1"></i> Completed
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <div class="mb-2"><i class="fas fa-calendar-times fa-3x text-secondary opacity-50"></i></div>
                                        <div class="fw-bold fs-6">No installment dues found for this month / filter.</div>
                                        <small>Choose "All Months" or use the filter options above to view dues across other months.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($installments->hasPages())
                    <div class="p-3 border-top d-flex justify-content-end no-print">
                        {{ $installments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL: Collect Payment Modal --}}
    <div class="modal fade" id="modalCollectPayment" tabindex="-1" aria-labelledby="modalCollectPaymentLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom bg-light px-3 py-2">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalCollectPaymentLabel">
                        <i class="fas fa-hand-holding-usd text-success me-1"></i> Collect Installment Payment
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formCollectPayment">
                    @csrf
                    <input type="hidden" id="collectInstallmentId" value="">
                    <input type="hidden" id="collectPlanId" value="">

                    <div class="modal-body p-3">
                        <div class="p-3 rounded-3 bg-light border mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted" style="font-size: 12px;">Installment:</span>
                                <span class="fw-bold text-dark" id="dispCollectInstNo">#1 of 12</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted" style="font-size: 12px;">Due Date:</span>
                                <span class="fw-bold text-secondary" id="dispCollectDueDate">—</span>
                            </div>
                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                <span class="fw-bold text-dark" style="font-size: 13px;">Due Amount:</span>
                                <span class="fw-bolder text-primary fs-6" id="dispCollectDueAmount">Rs. 0.00</span>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark mb-1">Paid Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="paid_amount" id="collectPaidAmount" class="form-control form-control-sm fw-bold text-end" required>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-dark mb-1">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="paid_date" id="collectPaidDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-dark mb-1">Payment Method</label>
                                <select name="payment_method" id="collectPaymentMethod" class="form-select form-select-sm">
                                    <option value="Cash" selected>Cash</option>
                                    <option value="Online / Bank Transfer">Online / Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark mb-1">Deposit To Account <span class="text-danger">*</span></label>
                            <select name="receipt_account_id" id="collectReceiptAccount" class="form-select form-select-sm" required>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ str_contains(strtolower($acc->title), 'cash') ? 'selected' : '' }}>
                                        {{ $acc->title }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 10.5px;">Cash / Bank Account where money is received</small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark mb-1">Notes / Remarks</label>
                            <input type="text" name="notes" id="collectNotes" class="form-control form-control-sm" placeholder="e.g. Receipt # or transaction ID">
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-top p-2 px-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3" id="btnSubmitCollect">
                            <i class="fas fa-check-circle me-1"></i> Confirm &amp; Save Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Initialize Select2 with customer search if available
    if ($('.select2-customer').length > 0) {
        $('.select2-customer').select2({
            placeholder: "All Customers",
            allowClear: true,
            width: '100%'
        });
    }

    // Open Collect Payment Modal
    $(document).on('click', '.btn-collect-inst', function() {
        var instId = $(this).data('inst-id');
        var planId = $(this).data('plan-id');
        var instNo = $(this).data('inst-no');
        var totalMonths = $(this).data('total-months');
        var dueDate = $(this).data('due-date');
        var dueAmt = parseFloat($(this).data('due-amount'));
        var paidAmt = parseFloat($(this).data('paid-amount')) || 0;
        var remainingDue = Math.max(0, dueAmt - paidAmt);

        $('#collectInstallmentId').val(instId);
        $('#collectPlanId').val(planId);
        $('#dispCollectInstNo').text('#' + instNo + ' of ' + totalMonths);
        $('#dispCollectDueDate').text(dueDate);
        $('#dispCollectDueAmount').text('Rs. ' + remainingDue.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#collectPaidAmount').val(remainingDue.toFixed(2));
        $('#collectNotes').val('Installment #' + instNo + ' payment');

        $('#modalCollectPayment').modal('show');
    });

    // Submit Collect Payment Form
    $('#formCollectPayment').on('submit', function(e) {
        e.preventDefault();
        var instId = $('#collectInstallmentId').val();
        var $btn = $('#btnSubmitCollect');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: '/installments/' + instId + '/collect',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Confirm &amp; Save Payment');
                if (res.ok) {
                    $('#modalCollectPayment').modal('hide');
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.message);
                    } else {
                        alert(res.message);
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 800);
                } else {
                    alert(res.message || 'Payment collection failed.');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Confirm &amp; Save Payment');
                var err = 'Failed to record installment payment.';
                if (xhr.responseJSON && xhr.responseJSON.message) err = xhr.responseJSON.message;
                alert(err);
            }
        });
    });
});
</script>
@endsection