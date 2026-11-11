@extends('admin_panel.layout.app')

@section('title', ($vendor->name ?? 'Vendor') . ' - ' . ($activeTab === 'backup' ? 'Backup Ledger' : ($activeTab === 'annual' ? 'Annual Rebate Ledger' : 'Ledger')))

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

    .vdetail-page {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: #1e293b;
        padding-bottom: 40px;
    }

    /* Page Header */
    .vdetail-header {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 20px 24px;
        border-radius: 16px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .vdetail-title {
        font-weight: 800;
        font-size: 1.35rem;
        color: #0f172a;
        margin-bottom: 2px;
        letter-spacing: -0.02em;
    }
    .vdetail-sub {
        font-size: 0.82rem;
        color: #64748b;
    }

    /* Navigation Tabs */
    .v-tabs-container {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 12px;
        flex-wrap: wrap;
    }
    .v-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 12px;
        font-size: 0.88rem;
        font-weight: 700;
        text-decoration: none;
        color: #64748b;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .v-tab-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .v-tab-btn.active-main {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #ffffff !important;
        border-color: #1d4ed8;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    .v-tab-btn.active-backup {
        background: linear-gradient(135deg, #10b981 0%, #047857 100%);
        color: #ffffff !important;
        border-color: #047857;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    }
    .v-tab-btn.active-annual {
        background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
        color: #ffffff !important;
        border-color: #6d28d9;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
    }
    .v-tab-badge {
        font-size: 0.72rem;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: 700;
    }
    .v-tab-btn.active-main .v-tab-badge,
    .v-tab-btn.active-backup .v-tab-badge,
    .v-tab-btn.active-annual .v-tab-badge {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }

    /* Back Button */
    .btn-vdetail-back {
        background: #ffffff;
        color: #475569 !important;
        border: 1.5px solid #cbd5e1;
        padding: 9px 18px;
        font-size: 0.86rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .btn-vdetail-back:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a !important;
    }

    /* Filter Card & Actions Grid */
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .filter-actions-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 8px;
        width: 100%;
    }
    .btn-filter-main {
        border-radius: 10px !important;
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%) !important;
        border: none !important;
        font-weight: 700 !important;
        height: 42px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        color: #ffffff !important;
        font-size: 0.86rem !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.22) !important;
    }
    .btn-filter-reset {
        border-radius: 10px !important;
        border: 1.5px solid #cbd5e1 !important;
        font-weight: 600 !important;
        height: 42px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: #475569 !important;
        text-decoration: none !important;
        font-size: 0.84rem !important;
        background: #ffffff !important;
    }
    .btn-filter-reset:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
    }
    .btn-filter-print {
        border-radius: 10px !important;
        border: 1.5px solid #cbd5e1 !important;
        font-weight: 600 !important;
        height: 42px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #ffffff !important;
        color: #475569 !important;
        font-size: 0.84rem !important;
    }
    .btn-filter-print:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
    }

    /* Stat KPI Cards */
    .kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .kpi-lbl {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 6px;
    }
    .kpi-val {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .kpi-closing-payable {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff;
        border: none;
    }
    .kpi-closing-advance {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
        border: none;
    }
    .kpi-closing-receivable {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: #ffffff;
        border: none;
    }
    .kpi-closing-annual {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        color: #ffffff;
        border: none;
    }
    .kpi-closing-receivable .kpi-lbl,
    .kpi-closing-annual .kpi-lbl,
    .kpi-closing-payable .kpi-lbl,
    .kpi-closing-advance .kpi-lbl {
        color: rgba(255, 255, 255, 0.88) !important;
    }

    /* Table & Container */
    .vdetail-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        overflow: hidden;
    }
    .vdetail-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .vdetail-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .vdetail-table {
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    .vdetail-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 20px;
    }
    .vdetail-table tbody td {
        padding: 14px 20px;
        vertical-align: middle;
        font-size: 0.88rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .vdetail-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Print Styles */
    @media print {
        .btn-vdetail-back, .filter-card, .btn, .v-tabs-container, .btn-settle-action {
            display: none !important;
        }
        .vdetail-page {
            padding: 0;
            background: #fff;
        }
        .vdetail-card {
            border: none !important;
            box-shadow: none !important;
        }
        .vdetail-table-wrap {
            display: block !important;
        }
    }
</style>

<div class="vdetail-page container-fluid px-3 px-md-4 pt-3">
    
    {{-- Header Row --}}
    <div class="vdetail-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <h3 class="vdetail-title">
                <i class="fas {{ $activeTab === 'backup' ? 'fa-shield-alt text-success' : ($activeTab === 'annual' ? 'fa-bullseye text-primary' : 'fa-receipt text-primary') }} me-2"></i>
                {{ $activeTab === 'backup' ? 'Vendor Backup Claim Ledger' : ($activeTab === 'annual' ? 'Vendor Annual Rebate Ledger' : 'Vendor Main Ledger') }}
            </h3>
            <div class="vdetail-sub">Transaction history for <span class="fw-bold text-primary">{{ $vendor->name }}</span></div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if ($activeTab !== 'main')
                <button type="button" class="btn btn-success fw-bold px-3 py-2 btn-settle-action" style="border-radius: 10px;" data-bs-toggle="modal" data-bs-target="#settleIncentiveModal" data-toggle="modal" data-target="#settleIncentiveModal">
                    <i class="fas fa-hand-holding-usd me-1"></i> Receive / Settle Claim
                </button>
            @endif
            <a href="{{ route('vendors-ledger') }}" class="btn-vdetail-back">
                <i class="fas fa-arrow-left"></i> Back to Vendor List
            </a>
        </div>
    </div>

    {{-- Multi-Ledger Navigation Tabs --}}
    <div class="v-tabs-container">
        <a href="{{ route('vendor.ledger', ['vendor' => $vendor->id, 'tab' => 'main', 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" 
           class="v-tab-btn {{ $activeTab === 'main' ? 'active-main' : '' }}">
            <i class="fas fa-file-invoice"></i> 1. Main Ledger (Bills & Payments)
            <span class="v-tab-badge bg-light text-dark">
                Rs. {{ number_format(abs($mainBalance), 2) }} {{ $mainBalance >= 0 ? 'Payable' : 'Advance' }}
            </span>
        </a>

        <a href="{{ route('vendor.ledger', ['vendor' => $vendor->id, 'tab' => 'backup', 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" 
           class="v-tab-btn {{ $activeTab === 'backup' ? 'active-backup' : '' }}">
            <i class="fas fa-shield-alt"></i> 2. Backup Claim Ledger
            <span class="v-tab-badge bg-light text-success fw-bold">
                Rs. {{ number_format($backupBalance, 2) }} Due
            </span>
        </a>

        <a href="{{ route('vendor.ledger', ['vendor' => $vendor->id, 'tab' => 'annual', 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" 
           class="v-tab-btn {{ $activeTab === 'annual' ? 'active-annual' : '' }}">
            <i class="fas fa-bullseye"></i> 3. Annual Rebate Ledger
            <span class="v-tab-badge bg-light text-primary fw-bold">
                Rs. {{ number_format($annualBalance, 2) }} Due
            </span>
        </a>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="col-12 col-md-4">
                <label class="form-label small text-secondary fw-bold mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control px-3 py-2" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}" style="border-radius: 10px; border: 1.5px solid #cbd5e1;">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-secondary fw-bold mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control px-3 py-2" value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}" style="border-radius: 10px; border: 1.5px solid #cbd5e1;">
            </div>
            <div class="col-12 col-md-4">
                <div class="filter-actions-grid">
                    <button type="submit" class="btn btn-primary btn-filter-main">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('vendor.ledger', ['vendor' => $vendor->id, 'tab' => $activeTab]) }}" class="btn btn-outline-secondary btn-filter-reset">Reset</a>
                    <button type="button" onclick="printVendorLedger()" class="btn btn-light btn-filter-print" title="Print Ledger">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if ($activeTab === 'main')
        {{-- ==================== TAB 1: MAIN LEDGER ==================== --}}
        {{-- Summary KPI Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-lbl">Opening Balance</div>
                    <div class="kpi-val text-dark">Rs. {{ number_format((float)$opening_balance, 2) }}</div>
                    <div class="small text-muted mt-1">As of {{ \Carbon\Carbon::parse(request('start_date', now()->startOfMonth()))->format('d/m/Y') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card {{ $closing_balance > 0 ? 'kpi-closing-payable' : ($closing_balance < 0 ? 'kpi-closing-advance' : '') }}">
                    <div class="kpi-lbl {{ $closing_balance != 0 ? 'text-white-50' : '' }}">Closing Balance</div>
                    <div class="kpi-val {{ $closing_balance != 0 ? 'text-white' : 'text-dark' }}">
                        Rs. {{ number_format(abs((float)$closing_balance), 2) }}
                    </div>
                    <div class="small mt-1 {{ $closing_balance != 0 ? 'text-white-50' : 'text-muted' }}">
                        {{ $closing_balance > 0 ? 'To be Paid (Payable Cr)' : ($closing_balance < 0 ? 'Advance (Dr)' : 'Settled') }}
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-lbl">Total Transactions</div>
                    <div class="kpi-val text-dark">{{ $transactions->count() }}</div>
                    <div class="small text-muted mt-1">In selected period</div>
                </div>
            </div>
        </div>

        {{-- Main Table View --}}
        <div class="vdetail-card">
            <div class="vdetail-card-header">
                <div class="fw-bold text-dark"><i class="fas fa-list me-1 text-muted"></i> Statement of Account (Main Bill & Payments)</div>
                <div class="text-muted small">Showing {{ $transactions->count() }} entries</div>
            </div>

            <div class="vdetail-table-wrap">
                <table class="table vdetail-table">
                    <thead>
                        <tr>
                            <th class="text-start ps-4" style="width: 110px;">Date</th>
                            <th class="text-start">Description</th>
                            <th class="text-end" style="width: 130px;">Debit (Dr)</th>
                            <th class="text-end" style="width: 130px;">Credit (Cr)</th>
                            <th class="text-end" style="width: 140px;">Balance</th>
                            <th class="text-center pe-4" style="width: 130px;">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($transactions->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                    <p class="mb-0">No transactions found in this period.</p>
                                </td>
                            </tr>
                        @else
                            @foreach ($transactions as $txn)
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark">
                                        {{ \Carbon\Carbon::parse($txn['date'])->format('d/m/Y') }}
                                    </td>
                                    <td class="text-secondary small">{{ $txn['description'] }}</td>
                                    <td class="text-end font-monospace text-dark">
                                        {{ $txn['debit'] > 0 ? number_format((float)$txn['debit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end font-monospace text-dark">
                                        {{ $txn['credit'] > 0 ? number_format((float)$txn['credit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end fw-bold font-monospace {{ $txn['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                        Rs. {{ number_format(abs((float)$txn['balance']), 2) }}
                                        <small class="fw-normal text-muted ms-1" style="font-size: 0.72rem;">{{ $txn['balance'] >= 0 ? 'Cr' : 'Dr' }}</small>
                                    </td>
                                    <td class="pe-4 text-center">
                                        @if ($txn['source_type'] === 'Purchase' || $txn['source_type'] === 'App\\Models\\Purchase')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Purchase</span>
                                        @elseif($txn['source_type'] === 'PurchaseReturn')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">Return</span>
                                        @elseif($txn['source_type'] === 'App\\Models\\VoucherMaster')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">Payment</span>
                                        @else
                                            <span class="badge bg-light text-secondary border rounded-pill px-3">{{ class_basename($txn['source_type']) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot class="bg-light border-top">
                        <tr>
                            <td colspan="4" class="text-end py-3 fw-bold text-dark text-uppercase small">Closing Balance:</td>
                            <td class="text-end py-3 fw-bold font-monospace {{ $closing_balance > 0 ? 'text-danger' : 'text-success' }}">
                                Rs. {{ number_format(abs((float)$closing_balance), 2) }}
                                <small class="fw-normal text-muted ms-1">{{ $closing_balance >= 0 ? 'Cr' : 'Dr' }}</small>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    @else
        {{-- ==================== TAB 2 & 3: BACKUP / ANNUAL INCENTIVE LEDGER ==================== --}}
        @php
            $currData = ($activeTab === 'backup') ? $backupData : $annualData;
            $themeColor = ($activeTab === 'backup') ? 'success' : 'primary';
            $kpiClass = ($activeTab === 'backup') ? 'kpi-closing-receivable' : 'kpi-closing-annual';
            $labelTitle = ($activeTab === 'backup') ? 'Backup Incentive Claims' : 'Annual Volume Rebates';
        @endphp

        {{-- Summary KPI Cards for Incentive --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-lbl">Opening Claim Balance</div>
                    <div class="kpi-val text-dark">Rs. {{ number_format((float)$currData['opening_balance'], 2) }}</div>
                    <div class="small text-muted mt-1">As of {{ \Carbon\Carbon::parse(request('start_date', now()->startOfMonth()))->format('d/m/Y') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card {{ $kpiClass }}">
                    <div class="kpi-lbl text-white-50">Current Outstanding Claim (Vendor se lena hai)</div>
                    <div class="kpi-val text-white">
                        Rs. {{ number_format((float)$currData['closing_balance'], 2) }}
                    </div>
                    <div class="small mt-1 text-white-50">
                        {{ $currData['closing_balance'] > 0 ? 'Receivable from Vendor' : 'Settled' }}
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-lbl">Total Period Claims / Settled</div>
                    <div class="kpi-val text-dark fs-5">
                        <span class="text-success fw-bold">+{{ number_format((float)$currData['total_claims'], 2) }}</span> / 
                        <span class="text-danger fw-bold">-{{ number_format((float)$currData['total_settled'], 2) }}</span>
                    </div>
                    <div class="small text-muted mt-1">Accrued Claims vs Payouts Received</div>
                </div>
            </div>
        </div>

        {{-- Incentive Table View --}}
        <div class="vdetail-card">
            <div class="vdetail-card-header">
                <div>
                    <span class="fw-bold text-dark fs-6"><i class="fas {{ $activeTab === 'backup' ? 'fa-shield-alt text-success' : 'fa-bullseye text-primary' }} me-1"></i> {{ $labelTitle }} Statement</span>
                    <span class="badge bg-{{ $themeColor }}-subtle text-{{ $themeColor }} ms-2 px-2 py-1">Off-Invoice Backend Ledger</span>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-{{ $themeColor }} fw-bold px-3 btn-settle-action" data-bs-toggle="modal" data-bs-target="#settleIncentiveModal" data-toggle="modal" data-target="#settleIncentiveModal">
                        <i class="fas fa-plus-circle me-1"></i> Record Payout / Adjustment
                    </button>
                </div>
            </div>

            <div class="vdetail-table-wrap">
                <table class="table vdetail-table">
                    <thead>
                        <tr>
                            <th class="text-start ps-4" style="width: 110px;">Date</th>
                            <th class="text-start" style="width: 150px;">Ref / Inv #</th>
                            <th class="text-start">Description & Notes</th>
                            <th class="text-end" style="width: 150px;">Claim Accrued (Dr)</th>
                            <th class="text-end" style="width: 150px;">Received/Adjusted (Cr)</th>
                            <th class="text-end" style="width: 160px;">Balance (Receivable)</th>
                            <th class="text-center pe-4" style="width: 130px;">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($currData['transactions']->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-clipboard-check fa-3x mb-3 text-light"></i>
                                    <p class="mb-0">No {{ strtolower($labelTitle) }} records found in this period.</p>
                                </td>
                            </tr>
                        @else
                            @foreach ($currData['transactions'] as $txn)
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark">
                                        {{ \Carbon\Carbon::parse($txn['date'])->format('d/m/Y') }}
                                    </td>
                                    <td class="fw-bold text-dark font-monospace">
                                        @if ($txn['purchase_id'])
                                            <a href="{{ route('purchase.invoice', $txn['purchase_id']) }}" class="text-decoration-none text-primary" target="_blank">
                                                {{ $txn['reference_no'] ?: 'PUR #' . $txn['purchase_id'] }} <i class="fas fa-external-link-alt small ms-1" style="font-size: 10px;"></i>
                                            </a>
                                        @else
                                            {{ $txn['reference_no'] ?: '—' }}
                                        @endif
                                    </td>
                                    <td class="text-secondary small">{{ $txn['description'] }}</td>
                                    <td class="text-end font-monospace text-success fw-bold">
                                        {{ $txn['debit'] > 0 ? '+Rs. ' . number_format((float)$txn['debit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end font-monospace text-danger fw-bold">
                                        {{ $txn['credit'] > 0 ? '-Rs. ' . number_format((float)$txn['credit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end fw-bold font-monospace text-dark">
                                        Rs. {{ number_format((float)$txn['balance'], 2) }}
                                    </td>
                                    <td class="pe-4 text-center">
                                        @if ($txn['entry_type'] === 'claim')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Purchase Claim</span>
                                        @elseif($txn['entry_type'] === 'received')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Cash/Bank Payout</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-3">Bill Adjusted</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot class="bg-light border-top">
                        <tr>
                            <td colspan="5" class="text-end py-3 fw-bold text-dark text-uppercase small">Current Claim Balance Due:</td>
                            <td class="text-end py-3 fw-bold font-monospace text-success fs-6">
                                Rs. {{ number_format((float)$currData['closing_balance'], 2) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

</div>

{{-- Settle / Payout Modal --}}
<div class="modal fade" id="settleIncentiveModal" tabindex="-1" aria-labelledby="settleIncentiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('vendor.incentive.settle', $vendor->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fas fa-hand-holding-usd text-success me-2"></i> Receive / Settle Incentive Claim
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Claim Ledger Type</label>
                        <select name="claim_type" id="modalClaimType" class="form-select fw-semibold" required>
                            <option value="backup" {{ $activeTab === 'backup' ? 'selected' : '' }}>Backup Incentive Ledger (Current Due: Rs. {{ number_format($backupBalance, 2) }})</option>
                            <option value="annual" {{ $activeTab === 'annual' ? 'selected' : '' }}>Annual Rebate Ledger (Current Due: Rs. {{ number_format($annualBalance, 2) }})</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Settlement Mode</label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check p-2 border rounded" style="background: #F8FAFC;">
                                <input class="form-check-input ms-0 me-2" type="radio" name="settlement_type" id="settleCashBank" value="cash_bank" checked onchange="toggleSettlementMode()">
                                <label class="form-check-label fw-bold text-dark" for="settleCashBank">
                                    <i class="fas fa-money-bill-wave text-success me-1"></i> Cash / Bank Received (Vendor Paid Payout)
                                    <small class="text-muted d-block fw-normal">Vendor ne cheque ya cash payout bhej diya hai.</small>
                                </label>
                            </div>
                            <div class="form-check p-2 border rounded" style="background: #F8FAFC;">
                                <input class="form-check-input ms-0 me-2" type="radio" name="settlement_type" id="settleAdjustPayable" value="adjust_payable" onchange="toggleSettlementMode()">
                                <label class="form-check-label fw-bold text-dark" for="settleAdjustPayable">
                                    <i class="fas fa-cut text-danger me-1"></i> Adjust Against Main Vendor Bill (Offset Payable)
                                    <small class="text-muted d-block fw-normal">Vendor ke main bill (payable) me se yeh amount minus ho jayegi.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="paymentAccountWrap">
                        <label class="form-label fw-bold small text-muted">Deposit Into (Cash/Bank Account) <span class="text-danger">*</span></label>
                        <select name="account_id" id="modalAccountId" class="form-select">
                            <option value="">-- Select Cash/Bank Account --</option>
                            @foreach ($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->title }} ({{ $acc->head->name ?? 'Account' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Settlement Date</label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Amount (Rs) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="modalAmount" class="form-control fw-bold text-end" 
                                   value="{{ $activeTab === 'backup' ? ($backupBalance > 0 ? $backupBalance : '') : ($annualBalance > 0 ? $annualBalance : '') }}" 
                                   placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold small text-muted">Remarks / Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Paid via cheque #1234 or Year-end incentive settlement">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fas fa-check me-1"></i> Confirm Settlement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function printVendorLedger() {
        let name = "{{ addslashes($vendor->name ?? 'Vendor') }}";
        let tab = "{{ $activeTab }}";
        let titleSuffix = tab === 'backup' ? 'Backup Claim Ledger' : (tab === 'annual' ? 'Annual Rebate Ledger' : 'Ledger');
        let cleanName = name.replace(/[\/\\:*?"<>|]/g, ' ').replace(/\s+/g, ' ').trim();
        document.title = (cleanName || 'Vendor') + ' - ' + titleSuffix;
        window.print();
    }

    function toggleSettlementMode() {
        const isAdjust = $('#settleAdjustPayable').is(':checked');
        if (isAdjust) {
            $('#paymentAccountWrap').slideUp();
            $('#modalAccountId').prop('required', false);
        } else {
            $('#paymentAccountWrap').slideDown();
            $('#modalAccountId').prop('required', true);
        }
    }

    $('#modalClaimType').on('change', function() {
        const type = $(this).val();
        if (type === 'backup') {
            $('#modalAmount').val("{{ $backupBalance > 0 ? $backupBalance : '' }}");
        } else {
            $('#modalAmount').val("{{ $annualBalance > 0 ? $annualBalance : '' }}");
        }
    });

    $(document).ready(function() {
        toggleSettlementMode();
    });
</script>
@endpush
