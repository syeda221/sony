<?php

$indexPath = __DIR__ . '/../resources/views/admin_panel/sale/installments/index.blade.php';

$blade = <<<'BLADE'
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
        .filter-panel .select2-container--default.select2-container--focus .select2-selection--single,
        .filter-panel .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
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

        .btn-premium-action {
            background-color: #f8fafc !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #475569 !important;
            font-weight: 700 !important;
            border-radius: 6px !important;
            height: 32px !important;
            padding: 0 12px !important;
            font-size: 11px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s ease-in-out !important;
        }
        .btn-premium-action:hover {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
    </style>

    <div class="page-content-wrapper p-3 p-md-4">
        {{-- Page Header --}}
        <div class="row align-items-center justify-content-between mb-4">
            <div class="col-12 col-md-auto mb-3 mb-md-0">
                <h3 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="fas fa-calendar-alt text-primary"></i> Sale Installment Plans
                </h3>
                <p class="text-muted small mb-0">Manage customer installment plans, down payments, monthly dues &amp; collections</p>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                <a href="{{ route('installments.monthly_report') }}" class="btn btn-premium-secondary px-3 shadow-sm">
                    <i class="fas fa-file-invoice-dollar me-1 text-primary"></i> Monthly Recovery Report
                </a>
                <a href="{{ route('sale.add') }}" class="btn btn-premium-primary px-3 shadow-sm">
                    <i class="fas fa-plus me-1"></i> New Installment Sale
                </a>
            </div>
        </div>

        {{-- English Real-time Due Alert Banner --}}
        <div id="installmentLiveAlerts" style="display: none;" class="mb-4">
            <div class="alert alert-warning border-warning shadow-sm rounded-3 p-3 mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2" style="background-color: #fefce8; border: 1.5px solid #facc15;">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0" style="width: 38px; height: 38px;">
                        <i class="fas fa-bell fs-6"></i>
                    </div>
                    <div>
                        <strong class="text-dark d-block" style="font-size: 13.5px;" id="installmentLiveAlertTitle">Installment Due Alerts</strong>
                        <span class="small text-secondary" id="installmentLiveAlertBody">Checking upcoming recoveries...</span>
                    </div>
                </div>
                <a href="{{ route('installments.monthly_report', ['filter' => 'due_soon']) }}" class="btn btn-sm btn-dark fw-bold px-3">
                    <i class="fas fa-eye me-1"></i> View Due Customers
                </a>
            </div>
        </div>

        {{-- KPI Stat Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sale-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Total Plans</div>
                            <h4 class="fw-bold text-dark mb-0 mt-1">{{ number_format($totalPlansCount ?? $plans->total()) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #eff6ff; color: #2563eb;">
                            <i class="fas fa-file-contract"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sale-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Active Plans</div>
                            <h4 class="fw-bold text-primary mb-0 mt-1">{{ number_format($totalActivePlans) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #faf5ff; color: #7e22ce;">
                            <i class="fas fa-play-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sale-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Total Financed</div>
                            <h4 class="fw-bold text-dark mb-0 mt-1" style="font-size: 1.05rem;">Rs. {{ number_format($totalFinanced, 0) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #f0f9ff; color: #0284c7;">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sale-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Total Collected</div>
                            <h4 class="fw-bold text-success mb-0 mt-1" style="font-size: 1.05rem;">Rs. {{ number_format($totalCollected, 0) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #ecfdf5; color: #059669;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sale-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Pending Dues</div>
                            <h4 class="fw-bold text-warning mb-0 mt-1" style="font-size: 1.05rem; color: #d97706 !important;">Rs. {{ number_format($totalPending, 0) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #fffbeb; color: #d97706;">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sale-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 10.5px;">Overdue Dues</div>
                            <h4 class="fw-bold text-danger mb-0 mt-1">{{ number_format($overdueCount) }}</h4>
                        </div>
                        <div class="sale-stat-icon" style="background-color: #fef2f2; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Filter Pills --}}
        <div class="mb-4 sales-status-pills">
            <a href="{{ route('installments.index', ['status' => 'all', 'search' => $search, 'customer_id' => $customerId]) }}"
                class="btn btn-sm {{ $status === 'all' || !$status ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-3 shadow-sm px-3 fw-bold">
                All Plans <span class="badge bg-white text-dark ms-1">{{ $totalPlansCount ?? $plans->total() }}</span>
            </a>
            <a href="{{ route('installments.index', ['status' => 'active', 'search' => $search, 'customer_id' => $customerId]) }}"
                class="btn btn-sm {{ $status === 'active' ? 'btn-primary text-white' : 'btn-outline-primary' }} rounded-3 shadow-sm px-3 fw-bold">
                <i class="fas fa-play-circle me-1"></i> Active <span class="badge bg-white text-primary ms-1">{{ $totalActivePlans }}</span>
            </a>
            <a href="{{ route('installments.index', ['status' => 'completed', 'search' => $search, 'customer_id' => $customerId]) }}"
                class="btn btn-sm {{ $status === 'completed' ? 'btn-success text-white' : 'btn-outline-success' }} rounded-3 shadow-sm px-3 fw-bold">
                <i class="fas fa-check-circle me-1"></i> Completed <span class="badge bg-white text-success ms-1">{{ $totalCompletedPlans ?? 0 }}</span>
            </a>
        </div>

        {{-- Main Table Container Card --}}
        <div class="card premium-card">
            <div class="card-body p-3 p-md-4">
                {{-- Session Flash Alerts --}}
                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 mb-4">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ session('error') }}</span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Clean & Bold Filter Panel (Exact Sale Page Pattern) --}}
                <div class="filter-panel mb-4">
                    <form method="GET" action="{{ route('installments.index') }}" id="installmentsFilterForm" class="row g-2 align-items-end">
                        <input type="hidden" name="status" value="{{ $status }}">

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
                            <input type="text" class="form-control" name="bill_no" id="filter_bill_no" value="{{ $billNo }}" placeholder="e.g. INV-0001">
                        </div>

                        {{-- Duration Dropdown --}}
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">Plan Duration</label>
                            <select name="duration_months" class="form-select">
                                <option value="">All Durations</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $durationMonths == $m ? 'selected' : '' }}>
                                        {{ $m }} {{ $m == 1 ? 'Month' : 'Months' }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        {{-- From Date --}}
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">From Date</label>
                            <input type="date" class="form-control" name="from_date" value="{{ $fromDate }}">
                        </div>

                        {{-- To Date --}}
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">To Date</label>
                            <input type="date" class="form-control" name="to_date" value="{{ $toDate }}">
                        </div>

                        {{-- General Keyword / Search --}}
                        <div class="col-12 col-md-1">
                            <label class="form-label mb-1">Keyword</label>
                            <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="Search...">
                        </div>

                        {{-- Action Buttons --}}
                        <div class="col-12 d-flex justify-content-end gap-2 mt-2 filter-actions-row">
                            <a href="{{ route('installments.index') }}" class="btn btn-premium-secondary px-3" id="btnReset">
                                <i class="fas fa-undo me-1"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-premium-primary px-4" id="btnSearch">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Plans Listing Table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle premium-table mb-0" style="width: 100%;">
                        <thead>
                            <tr>
                                <th class="ps-3 py-3" style="width: 50px;">#</th>
                                <th class="py-3">Invoice #</th>
                                <th class="py-3">Customer</th>
                                <th class="py-3 text-end">Total Net</th>
                                <th class="py-3 text-end">Advance Paid</th>
                                <th class="py-3 text-end">Financed Amt</th>
                                <th class="py-3 text-center">Duration</th>
                                <th class="py-3 text-end">Monthly Due</th>
                                <th class="py-3" style="min-width: 150px;">Recovery Progress</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="pe-3 py-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plans as $plan)
                                @php
                                    $paidCount = $plan->installments ? $plan->installments->where('status', 'paid')->count() : 0;
                                    $totalMonths = (int) $plan->duration_months;
                                    $pct = $totalMonths > 0 ? round(($paidCount / $totalMonths) * 100) : 0;
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-bold text-muted">PLN-{{ str_pad($plan->id, 4, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        @if($plan->sale)
                                            <a href="{{ route('sales.invoice', $plan->sale->id) }}" class="fw-bold text-primary text-decoration-none" target="_blank" title="View Sale Invoice">
                                                <i class="fas fa-file-invoice me-1"></i>{{ $plan->sale->invoice_no }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $plan->customer->customer_name ?? 'Walk-in Customer' }}</div>
                                        @if(!empty($plan->customer && $plan->customer->mobile))
                                            <small class="text-muted"><i class="fas fa-phone-alt me-1"></i>{{ $plan->customer->mobile }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        Rs. {{ number_format($plan->total_amount, 2) }}
                                    </td>
                                    <td class="text-end text-success fw-semibold">
                                        Rs. {{ number_format($plan->advance_amount, 2) }}
                                    </td>
                                    <td class="text-end fw-bold" style="color: #7e22ce;">
                                        Rs. {{ number_format($plan->financed_amount, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                            {{ $plan->duration_months }} Mos
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold" style="color: #6b21a8;">
                                        Rs. {{ number_format($plan->monthly_installment, 2) }} <span class="small text-muted fw-normal">/mo</span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 11px;">
                                            <span class="fw-bold text-secondary">{{ $paidCount }} / {{ $totalMonths }} Paid</span>
                                            <span class="fw-bold text-primary">{{ $pct }}%</span>
                                        </div>
                                        <div class="progress" style="height: 6px; border-radius: 4px; background-color: #e2e8f0;">
                                            <div class="progress-bar {{ $pct == 100 ? 'bg-success' : 'bg-primary' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $pct }}%;" 
                                                 aria-valuenow="{{ $pct }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($plan->status === 'completed')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <i class="fas fa-check me-1"></i> Completed
                                            </span>
                                        @else
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                <i class="fas fa-clock me-1"></i> Active
                                            </span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-premium-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Action
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button type="button" class="dropdown-item btn-view-plan" data-plan-id="{{ $plan->id }}">
                                                        <i class="fas fa-calendar-check me-2 text-primary"></i> Schedule &amp; Collect
                                                    </button>
                                                </li>
                                                @if($plan->sale)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('sales.invoice', $plan->sale->id) }}" target="_blank">
                                                            <i class="fas fa-print me-2 text-secondary"></i> Print Sale Invoice
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">
                                        <div class="mb-2"><i class="fas fa-folder-open fa-3x text-secondary opacity-50"></i></div>
                                        <div class="fw-bold fs-6">No installment plans found</div>
                                        <small>Sales created with the Installment toggle turned ON will appear here automatically.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($plans->hasPages())
                    <div class="p-3 border-top d-flex justify-content-end">
                        {{ $plans->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL 1: Plan Details & Installment Schedule --}}
    <div class="modal fade" id="modalPlanDetails" tabindex="-1" aria-labelledby="modalPlanDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom bg-light px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; background: #2563eb;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="modalPlanDetailsLabel">Installment Schedule</h5>
                            <small class="text-muted" id="modalPlanSubtitle">Plan Details &amp; Monthly Dues</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Top Plan Summary Banner --}}
                    <div class="row g-3 p-3 rounded-3 mb-4 text-white" style="background: linear-gradient(135deg, #1e3a8a, #2563eb);">
                        <div class="col-md-3 col-6 border-end border-white border-opacity-25">
                            <small class="text-uppercase opacity-75 d-block" style="font-size: 10px;">Customer</small>
                            <span class="fw-bold fs-6" id="mCustName">—</span>
                            <small class="d-block text-white opacity-75" id="mCustMobile" style="font-size: 11px;"></small>
                        </div>
                        <div class="col-md-3 col-6 border-end border-white border-opacity-25">
                            <small class="text-uppercase opacity-75 d-block" style="font-size: 10px;">Total Bill / Advance</small>
                            <span class="fw-bold fs-6" id="mTotalAmount">Rs. 0.00</span>
                            <small class="d-block text-warning" id="mAdvanceAmount" style="font-size: 11.5px;">Advance: Rs. 0.00</small>
                        </div>
                        <div class="col-md-3 col-6 border-end border-white border-opacity-25">
                            <small class="text-uppercase opacity-75 d-block" style="font-size: 10px;">Financed / Monthly</small>
                            <span class="fw-bold fs-6" id="mFinancedAmount">Rs. 0.00</span>
                            <small class="d-block text-white opacity-75" id="mMonthlyInstallment" style="font-size: 11.5px;">Rs. 0.00 / Mo</small>
                        </div>
                        <div class="col-md-3 col-6">
                            <small class="text-uppercase opacity-75 d-block" style="font-size: 10px;">Progress</small>
                            <span class="fw-bold fs-6" id="mProgressText">0 / 0 Paid</span>
                            <span class="badge bg-white text-dark mt-1" id="mPlanStatus">Active</span>
                        </div>
                    </div>

                    {{-- Installment Schedule Table --}}
                    <div class="table-responsive border rounded-3 overflow-hidden">
                        <table class="table table-hover align-middle mb-0" id="tableModalSchedule">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2 ps-3">#</th>
                                    <th class="py-2">Due Date</th>
                                    <th class="py-2 text-end">Due Amount</th>
                                    <th class="py-2 text-end">Paid Amount</th>
                                    <th class="py-2 text-center">Status</th>
                                    <th class="py-2">Paid Date / Receipt</th>
                                    <th class="py-2 pe-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyModalSchedule">
                                {{-- Dynamically loaded --}}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: Collect Payment Modal --}}
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
                            <label class="form-label small fw-bold text-dark mb-1">Notes / Receipt Remarks</label>
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
    var activePlanId = null;

    // Initialize Select2 with customer search if available
    if ($('.select2-customer').length > 0) {
        $('.select2-customer').select2({
            placeholder: "All Customers",
            allowClear: true,
            width: '100%'
        });
    }

    // Fetch live English Due Alerts for the alert banner
    $.ajax({
        url: "{{ route('installments.due_alerts') }}",
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.ok && res.total_alerts > 0) {
                var upcomingText = res.upcoming_count + ' installment(s) due within the next 48 hours';
                var overdueText = res.overdue_count > 0 ? (' and ' + res.overdue_count + ' overdue installment(s)') : '';
                $('#installmentLiveAlertBody').text('Action Notice: You have ' + upcomingText + overdueText + '. Click to review and send reminders.');
                $('#installmentLiveAlerts').slideDown(250);
            }
        }
    });

    // View Schedule Modal
    $(document).on('click', '.btn-view-plan', function() {
        var planId = $(this).data('plan-id');
        activePlanId = planId;
        loadPlanDetails(planId);
    });

    function loadPlanDetails(planId) {
        $('#tbodyModalSchedule').html('<tr><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading installment schedule...</td></tr>');
        $('#modalPlanDetails').modal('show');

        $.ajax({
            url: '/installments/' + planId,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.ok && res.plan) {
                    var p = res.plan;
                    var c = p.customer || {};
                    var s = p.sale || {};

                    $('#modalPlanSubtitle').text('Plan #PLN-' + String(p.id).padStart(4, '0') + ' | Sale: ' + (s.invoice_no || '—'));
                    $('#mCustName').text(c.customer_name || 'Walk-in Customer');
                    $('#mCustMobile').text(c.mobile ? 'Mobile: ' + c.mobile : '');
                    $('#mTotalAmount').text('Rs. ' + parseFloat(p.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#mAdvanceAmount').text('Advance: Rs. ' + parseFloat(p.advance_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#mFinancedAmount').text('Rs. ' + parseFloat(p.financed_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#mMonthlyInstallment').text('Rs. ' + parseFloat(p.monthly_installment).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' / Mo');

                    var paidCount = 0;
                    var rowsHtml = '';

                    if (p.installments && p.installments.length > 0) {
                        p.installments.forEach(function(inst) {
                            var isPaid = inst.status === 'paid';
                            var isPartial = inst.status === 'partial';
                            if (isPaid) paidCount++;

                            var statusBadge = '';
                            if (isPaid) {
                                statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check me-1"></i> Paid</span>';
                            } else if (isPartial) {
                                statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="fas fa-adjust me-1"></i> Partial</span>';
                            } else {
                                statusBadge = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="fas fa-clock me-1"></i> Pending</span>';
                            }

                            var paidDetails = '—';
                            if (inst.paid_date) {
                                paidDetails = '<div><strong class="text-dark">' + inst.paid_date + '</strong></div>' +
                                              '<small class="text-muted">' + (inst.receipt_account ? inst.receipt_account.title : (inst.payment_method || 'Cash')) + '</small>';
                            }

                            var actionBtn = '';
                            if (!isPaid) {
                                actionBtn = '<button type="button" class="btn btn-xs btn-success btn-collect-inst py-1 px-2 fw-semibold" ' +
                                            'data-inst-id="' + inst.id + '" ' +
                                            'data-plan-id="' + p.id + '" ' +
                                            'data-inst-no="' + inst.installment_no + '" ' +
                                            'data-total-months="' + p.duration_months + '" ' +
                                            'data-due-date="' + inst.due_date + '" ' +
                                            'data-due-amount="' + inst.amount + '" ' +
                                            'data-paid-amount="' + (inst.paid_amount || 0) + '">' +
                                            '<i class="fas fa-cash-register me-1"></i> Collect</button>';
                            } else {
                                actionBtn = '<span class="text-success small fw-bold"><i class="fas fa-check-double me-1"></i> Completed</span>';
                            }

                            rowsHtml += '<tr>' +
                                '<td class="ps-3"><span class="badge bg-light text-dark border">#' + inst.installment_no + '</span></td>' +
                                '<td class="fw-semibold text-secondary">' + inst.due_date + '</td>' +
                                '<td class="text-end fw-bold text-dark">Rs. ' + parseFloat(inst.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>' +
                                '<td class="text-end fw-semibold text-success">Rs. ' + parseFloat(inst.paid_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>' +
                                '<td class="text-center">' + statusBadge + '</td>' +
                                '<td>' + paidDetails + '</td>' +
                                '<td class="pe-3 text-end">' + actionBtn + '</td>' +
                            '</tr>';
                        });
                    } else {
                        rowsHtml = '<tr><td colspan="7" class="text-center py-4 text-muted">No installments generated for this plan.</td></tr>';
                    }

                    $('#tbodyModalSchedule').html(rowsHtml);
                    $('#mProgressText').text(paidCount + ' / ' + p.duration_months + ' Paid');
                    $('#mPlanStatus').text(p.status.toUpperCase()).removeClass('bg-success bg-primary').addClass(p.status === 'completed' ? 'bg-success' : 'bg-primary');
                } else {
                    $('#tbodyModalSchedule').html('<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load installment details.</td></tr>');
                }
            },
            error: function() {
                $('#tbodyModalSchedule').html('<tr><td colspan="7" class="text-center py-4 text-danger">Server connection error. Please try again.</td></tr>');
            }
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
                    if (activePlanId) {
                        loadPlanDetails(activePlanId);
                    }
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
BLADE;

file_put_contents($indexPath, $blade);
echo "installments/index.blade.php written successfully.\n";
