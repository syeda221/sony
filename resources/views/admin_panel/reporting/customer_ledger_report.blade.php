@extends('admin_panel.layout.app')

@section('title', 'Customer - Ledger')

@section('content')
<style>
    /* Standardized Sale Report Pattern Styling */
    .sale-report-container {
        padding: 10px 14px;
        background: #f1f5f9;
        min-height: calc(100vh - 75px);
    }
    .sale-filter-label {
        margin-right: 4px !important;
        margin-bottom: 0 !important;
        white-space: nowrap;
        font-weight: 700;
        font-size: .78rem;
        color: #475569;
    }
    .summary-pill-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        overflow-x: auto;
        white-space: nowrap;
    }
    .stat-pill {
        flex: 1 1 0px;
        padding: 6px 10px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        text-align: center;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .stat-pill .stat-label {
        font-size: .60rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 1px;
    }
    .stat-pill .stat-val {
        font-size: .88rem;
        font-weight: 800;
        line-height: 1.2;
    }

    /* Mobile Cards Styling */
    .mob-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .mob-metric-card {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 10px 6px;
        text-align: center;
        height: 100%;
    }
    .mob-metric-label {
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
    }
    .mob-metric-val {
        font-size: 14px;
        font-weight: 800;
    }

    /* Table Styling with Sticky Header */
    .sale-table-wrap {
        height: calc(100vh - 250px);
        max-height: calc(100vh - 250px);
        min-height: 380px;
        overflow-y: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
    }
    .report-table {
        font-size: .78rem;
        margin-bottom: 0;
    }
    .report-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #1e293b !important;
        color: #ffffff !important;
        font-size: .75rem;
        font-weight: 700;
        padding: 9px 10px;
        border-bottom: 2px solid #334155;
        white-space: nowrap;
    }

    .balance-positive {
        color: #16a34a;
        font-weight: bold;
    }
    .balance-negative {
        color: #dc2626;
        font-weight: bold;
    }

    @media print {
        body { background: #ffffff !important; font-size: 12px; color: #000; margin: 0; padding: 0; }
        .no-print, .rt_nav_header, header, .sidebar, .navbar, footer, #ledgerFormDesk, #ledgerFormMob, .summary-pill-bar, #ledgerHeader, .sale-table-wrap, #ledgerMobileContainer, .d-md-none.no-print { display: none !important; }
        .sale-report-container { padding: 0 !important; background: #fff !important; min-height: auto; }
        .card { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
        .print-container { display: block !important; width: 100%; }
        
        .print-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .print-table th { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 8px 4px; font-weight: bold; text-align: center; }
        .print-table td { border-bottom: 1px solid #eaeaea; padding: 6px 4px; text-align: center; }
        .print-table tr:last-child td { border-bottom: 2px solid #000; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .fw-bold { font-weight: bold !important; }
    }

    /* Collapsible Customer Container Styling (Colorful Accordion Cards) */
    .cust-acc-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
        padding: 10px 14px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    }
    .cust-acc-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left-width: 5px;
        border-radius: 14px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05);
        margin-bottom: 14px;
        overflow: hidden;
        transition: transform 0.15s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .cust-acc-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
    }
    .cust-acc-card.is-open {
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.10);
    }
    .cust-acc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 14px 18px;
        cursor: pointer;
        user-select: none;
        transition: filter 0.15s ease;
    }
    .cust-acc-header:hover {
        filter: brightness(0.985);
    }
    .cust-acc-left {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 240px;
        flex: 1 1 auto;
    }
    .cust-acc-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.14);
    }
    .cust-acc-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
    }
    .cust-acc-name {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.2px;
    }
    .cust-acc-chip {
        font-size: 0.70rem;
        font-weight: 700;
        padding: 2px 9px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.85);
        color: #334155;
        border: 1px solid #cbd5e1;
    }
    .cust-acc-sub {
        font-size: 0.74rem;
        color: #475569;
        margin-top: 3px;
    }
    .cust-acc-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .cust-kpi-box {
        min-width: 110px;
        padding: 6px 11px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: rgba(255, 255, 255, 0.92);
        text-align: right;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .cust-kpi-box.kpi-debit {
        background: #fef2f2;
        border-color: #fca5a5;
    }
    .cust-kpi-box.kpi-credit {
        background: #ecfdf5;
        border-color: #86efac;
    }
    .cust-kpi-box.kpi-closing {
        background: #eff6ff;
        border-color: #93c5fd;
    }
    .cust-kpi-val {
        font-size: 0.85rem;
        font-weight: 800;
        line-height: 1.2;
    }
    .cust-kpi-lbl {
        font-size: 0.60rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #475569;
    }
    .cust-acc-chevron {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease, background-color 0.15s ease, color 0.15s ease;
    }
    .cust-acc-card.is-open .cust-acc-chevron {
        transform: rotate(180deg);
        color: #ffffff;
    }

    /* 6 Rotating Customer Header Themes */
    .cust-theme-0 { border-left-color: #4f46e5; }
    .cust-theme-0 .cust-acc-header { background: linear-gradient(95deg, #eef2ff 0%, #f8faff 48%, #ffffff 100%); }
    .cust-theme-0 .cust-acc-icon { background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); }
    .cust-theme-0 .chip-entries { background: #e0e7ff; color: #3730a3; border-color: #c7d2fe; }
    .cust-theme-0.is-open .cust-acc-chevron { background: #4f46e5; border-color: #4f46e5; }

    .cust-theme-1 { border-left-color: #059669; }
    .cust-theme-1 .cust-acc-header { background: linear-gradient(95deg, #ecfdf5 0%, #f4fbf8 48%, #ffffff 100%); }
    .cust-theme-1 .cust-acc-icon { background: linear-gradient(135deg, #10b981 0%, #047857 100%); }
    .cust-theme-1 .chip-entries { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
    .cust-theme-1.is-open .cust-acc-chevron { background: #059669; border-color: #059669; }

    .cust-theme-2 { border-left-color: #0284c7; }
    .cust-theme-2 .cust-acc-header { background: linear-gradient(95deg, #e0f2fe 0%, #f3faff 48%, #ffffff 100%); }
    .cust-theme-2 .cust-acc-icon { background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%); }
    .cust-theme-2 .chip-entries { background: #bae6fd; color: #075985; border-color: #7dd3fc; }
    .cust-theme-2.is-open .cust-acc-chevron { background: #0284c7; border-color: #0284c7; }

    .cust-theme-3 { border-left-color: #d97706; }
    .cust-theme-3 .cust-acc-header { background: linear-gradient(95deg, #fffbeb 0%, #fefcf3 48%, #ffffff 100%); }
    .cust-theme-3 .cust-acc-icon { background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); }
    .cust-theme-3 .chip-entries { background: #fef3c7; color: #92400e; border-color: #fde68a; }
    .cust-theme-3.is-open .cust-acc-chevron { background: #d97706; border-color: #d97706; }

    .cust-theme-4 { border-left-color: #7c3aed; }
    .cust-theme-4 .cust-acc-header { background: linear-gradient(95deg, #f5f3ff 0%, #faf9ff 48%, #ffffff 100%); }
    .cust-theme-4 .cust-acc-icon { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); }
    .cust-theme-4 .chip-entries { background: #ede9fe; color: #5b21b6; border-color: #ddd6fe; }
    .cust-theme-4.is-open .cust-acc-chevron { background: #7c3aed; border-color: #7c3aed; }

    .cust-theme-5 { border-left-color: #e11d48; }
    .cust-theme-5 .cust-acc-header { background: linear-gradient(95deg, #fff1f2 0%, #fff8f9 48%, #ffffff 100%); }
    .cust-theme-5 .cust-acc-icon { background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%); }
    .cust-theme-5 .chip-entries { background: #ffe4e6; color: #9f1239; border-color: #fecdd3; }
    .cust-theme-5.is-open .cust-acc-chevron { background: #e11d48; border-color: #e11d48; }

    .cust-acc-body {
        display: none;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 14px 18px 16px 18px;
    }
    .cust-acc-inner-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }
    .cust-acc-table-wrap {
        max-height: 460px;
        overflow-y: auto;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #ffffff;
    }
    .cust-acc-table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #1e293b !important;
        color: #ffffff !important;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 2px solid #334155;
        padding: 10px 12px;
        white-space: nowrap;
    }
    .cust-acc-table tbody td {
        font-size: 0.79rem;
        padding: 9px 12px;
        vertical-align: middle;
    }
    @media print {
        .cust-acc-toolbar, #customerGroupsContainer { display: none !important; }
    }
</style>

<div class="sale-report-container">

    {{-- DESKTOP FILTER HEADER CARD (d-none d-md-block Standard Pattern) --}}
    <div class="card border-0 shadow-sm mb-2 no-print d-none d-md-block" style="border-radius: 10px;">
        <div class="card-body py-2 px-3">
            <form id="ledgerFormDesk">
                
                {{-- Top Section: Left Title, Mid Dates with Gap, Last Buttons --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-2 pb-2 border-bottom">
                    
                    {{-- Left Title --}}
                    <div class="d-flex align-items-center me-3">
                        <span class="fw-bold text-dark fs-6 text-nowrap" style="letter-spacing: -0.2px;">
                            <i class="fas fa-file-invoice-dollar text-primary me-2"></i>Customer Ledger Report
                        </span>
                    </div>

                    {{-- Mid Dates with Explicit Spacing Gap --}}
                    <div class="d-flex align-items-center me-auto flex-wrap" style="gap: 16px !important;">
                        <div class="d-flex align-items-center gap-1">
                            <label for="start_date_desk" class="sale-filter-label mb-0 ms-1 me-1">Start:</label>
                            <input type="date" name="start_date" id="start_date_desk" class="form-control form-control-sm fw-bold startDateInput" value="{{ date('Y-m-d') }}" style="height: 32px; width: 135px; font-size: .78rem; border-radius: 6px;">
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <label for="end_date_desk" class="sale-filter-label mb-0 ms-2 me-1">End:</label>
                            <input type="date" name="end_date" id="end_date_desk" class="form-control form-control-sm fw-bold endDateInput" value="{{ date('Y-m-d') }}" style="height: 32px; width: 135px; font-size: .78rem; border-radius: 6px;">
                        </div>
                    </div>

                    {{-- Last Buttons with X-Axis Gap --}}
                    <div class="d-flex align-items-center ms-auto" style="gap: 10px !important;">
                        <button type="button" class="btn btn-primary btn-sm px-3 fw-bold d-inline-flex align-items-center btnSearchTrigger" style="height: 32px; border-radius: 6px; font-size: .78rem; margin-right: 8px !important;">
                            <i class="fas fa-filter me-1"></i> Generate
                        </button>
                        <button type="button" class="btn btn-light border btn-sm px-3 fw-bold text-secondary d-inline-flex align-items-center btnResetTrigger" style="height: 32px; border-radius: 6px; font-size: .78rem; margin-right: 8px !important;">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold d-inline-flex align-items-center btnPrintReport" style="height: 32px; border-radius: 6px; font-size: .78rem;">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>

                                {{-- Location Hierarchy Row --}}
                <div class="row g-2 mb-2 pb-2 border-bottom">
                    <div class="col-md-4">
                        <label class="sale-filter-label mb-1">Province:</label>
                        <select name="province" id="province_ledger_desk" class="form-select form-select-sm provinceLedgerInput" style="height: 32px; font-size: .78rem; border-radius: 6px;">
                            <option value="">All Provinces</option>
                            @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="sale-filter-label mb-1">City:</label>
                        <select name="city" id="city_ledger_desk" class="form-select form-select-sm cityLedgerInput" style="height: 32px; font-size: .78rem; border-radius: 6px;">
                            <option value="">All Cities</option>
                            @foreach(app(\App\Services\LocationService::class)->getCities() as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="sale-filter-label mb-1">Area / Sector:</label>
                        <select name="area" id="area_ledger_desk" class="form-select form-select-sm areaLedgerInput" style="height: 32px; font-size: .78rem; border-radius: 6px;">
                            <option value="">All Areas</option>
                            @foreach(app(\App\Services\LocationService::class)->getAreas() as $a)
                                <option value="{{ $a }}">{{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Bottom Section: Zone, Customer, Quick Filter --}}
                <div class="row g-2">
                    <div class="col-md-4">
                        <label for="zone_id_desk" class="sale-filter-label mb-1">Zone:</label>
                        <select name="zone_id" id="zone_id_desk" class="form-select form-select-sm select2 zoneSelect">
                            <option value="">-- All Zones --</option>
                            @foreach ($zones as $z)
                                <option value="{{ $z->id }}">{{ $z->zone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="customer_id_desk" class="sale-filter-label mb-1">Customer:</label>
                        <select name="customer_id" id="customer_id_desk" class="form-select form-select-sm select2 customerSelect">
                            <option value="all" data-zone="">-- All Customers --</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" data-zone="{{ $c->zone }}" data-province="{{ $c->province ?? '' }}" data-city="{{ $c->city ?? '' }}" data-area="{{ $c->area ?? '' }}" {{ (string)request('customer_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="quick_filter_desk" class="sale-filter-label mb-1 d-block">Quick Filter:</label>
                        <select id="quick_filter_desk" class="form-control form-control-sm quickFilterSelect" style="height: 34px !important; font-size: .78rem; border-radius: 6px !important; border: 1px solid #cbd5e1 !important; background-color: #ffffff !important;">
                            <option value="custom">Custom Range</option>
                            <option value="daily">Daily (Today)</option>
                            <option value="weekly">Weekly (This Week)</option>
                            <option value="monthly">Monthly (This Month)</option>
                            <option value="yearly">Yearly (This Year)</option>
                        </select>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- MOBILE FILTER HEADER CARD (d-md-none With Top Margin to Prevent Navbar Overlap) --}}
    <div class="card border-0 shadow-sm mb-3 no-print d-md-none mt-2" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form id="ledgerFormMob">
                <div class="row g-2">
                    <div class="col-12 mb-1">
                        <span class="fw-bold text-dark fs-6">
                            <i class="fas fa-file-invoice-dollar text-primary me-2"></i>Customer Ledger
                        </span>
                    </div>

                                        <div class="col-4 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Province</label>
                        <select name="province" id="province_ledger_mob" class="form-select form-select-sm provinceLedgerInput" style="font-size: 11px;">
                            <option value="">All</option>
                            @foreach(app(\App\Services\LocationService::class)->getProvinces() as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">City</label>
                        <select name="city" id="city_ledger_mob" class="form-select form-select-sm cityLedgerInput" style="font-size: 11px;">
                            <option value="">All</option>
                            @foreach(app(\App\Services\LocationService::class)->getCities() as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Area</label>
                        <select name="area" id="area_ledger_mob" class="form-select form-select-sm areaLedgerInput" style="font-size: 11px;">
                            <option value="">All</option>
                            @foreach(app(\App\Services\LocationService::class)->getAreas() as $a)
                                <option value="{{ $a }}">{{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- 1. Zone --}}
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Zone</label>
                        <select name="zone_id" class="form-select form-select-sm select2 zoneSelect">
                            <option value="">-- All Zones --</option>
                            @foreach ($zones as $z)
                                <option value="{{ $z->id }}">{{ $z->zone }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 2. Customer --}}
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Customer</label>
                        <select name="customer_id" class="form-select form-select-sm select2 customerSelect">
                            <option value="all" data-zone="">-- All Customers --</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" data-zone="{{ $c->zone }}" data-province="{{ $c->province ?? '' }}" data-city="{{ $c->city ?? '' }}" data-area="{{ $c->area ?? '' }}" {{ (string)request('customer_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Quick Filter --}}
                    <div class="col-12 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Quick Filter</label>
                        <select class="form-control form-control-sm quickFilterSelect" style="font-size: 11px; height: 34px !important; border-radius: 6px !important; border: 1px solid #cbd5e1 !important; background-color: #ffffff !important;">
                            <option value="custom">Custom Range</option>
                            <option value="daily">Daily (Today)</option>
                            <option value="weekly">Weekly (This Week)</option>
                            <option value="monthly">Monthly (This Month)</option>
                            <option value="yearly">Yearly (This Year)</option>
                        </select>
                    </div>

                    {{-- 4. Start & End Date --}}
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">Start Date</label>
                        <input type="date" name="start_date" id="start_date_mob" class="form-control form-control-sm startDateInput" value="{{ date('Y-m-d') }}" style="font-size: 11px;">
                    </div>
                    <div class="col-6 mb-1">
                        <label class="form-label mb-1 fw-bold text-secondary" style="font-size: 11px;">End Date</label>
                        <input type="date" name="end_date" id="end_date_mob" class="form-control form-control-sm endDateInput" value="{{ date('Y-m-d') }}" style="font-size: 11px;">
                    </div>

                    {{-- 5. Full Width Generate Button --}}
                    <div class="col-12 my-2">
                        <button type="button" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow-sm btnSearchTrigger" style="background-color: #3b82f6; border-color: #3b82f6; font-size: 13px;">
                            <i class="fas fa-filter me-1"></i> Generate Report
                        </button>
                    </div>

                    {{-- 6. Centralized Reset & Print Actions With Horizontal Gap --}}
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-center gap-2 pt-1" style="gap: 10px !important;">
                            <button type="button" class="btn btn-light border btn-sm flex-fill fw-bold text-secondary btnResetTrigger" style="font-size: 11px; margin-right: 8px !important;">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill fw-bold btnPrintReport" style="font-size: 11px;">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DESKTOP SUMMARY METRIC PILL BAR (d-none d-md-block) --}}
    <div class="card border-0 shadow-sm mb-2 d-none d-md-block" style="border-radius: 10px; background: #ffffff;">
        <div class="card-body p-2">
            <div class="summary-pill-bar">
                
                <div class="stat-pill" style="background: #f8fafc; border-color: #cbd5e1;">
                    <div class="stat-label text-muted">Opening Balance</div>
                    <div class="stat-val text-dark" id="pillOpeningBalance">Rs 0.00</div>
                </div>

                <div class="stat-pill" style="background: #fef2f2; border-color: #fca5a5;">
                    <div class="stat-label text-danger">Total Debit (Dr)</div>
                    <div class="stat-val text-danger" id="pillTotalDebit">Rs 0.00</div>
                </div>

                <div class="stat-pill" style="background: #f0fdf4; border-color: #86efac;">
                    <div class="stat-label text-success">Total Credit (Cr)</div>
                    <div class="stat-val text-success" id="pillTotalCredit">Rs 0.00</div>
                </div>

                <div class="stat-pill" style="background: #f0f9ff; border-color: #bae6fd;">
                    <div class="stat-label text-info">Net Closing Balance</div>
                    <div class="stat-val text-primary" id="pillClosingBalance">Rs 0.00</div>
                </div>

            </div>
        </div>
    </div>

    {{-- MOBILE SUMMARY METRIC GRID (2 Columns col-6 d-md-none) --}}
    <div class="row g-2 mb-3 d-md-none no-print px-1">
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-wallet text-muted me-1"></i>Opening</span>
                <div class="mob-metric-val text-dark mt-1" id="mobPillOpeningBalance">Rs 0.00</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-arrow-up text-danger me-1"></i>Debit (Dr)</span>
                <div class="mob-metric-val text-danger mt-1" id="mobPillTotalDebit">Rs 0.00</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-arrow-down text-success me-1"></i>Credit (Cr)</span>
                <div class="mob-metric-val text-success mt-1" id="mobPillTotalCredit">Rs 0.00</div>
            </div>
        </div>
        <div class="col-6 mb-1">
            <div class="mob-metric-card">
                <span class="mob-metric-label"><i class="fas fa-balance-scale text-primary me-1"></i>Closing</span>
                <div class="mob-metric-val text-primary mt-1" id="mobPillClosingBalance">Rs 0.00</div>
            </div>
        </div>
    </div>

    {{-- Report Content Box --}}
    <div id="loader" style="display:none; text-align:center; padding: 20px;">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="small text-muted mt-2">Generating Customer Ledger Report…</div>
    </div>

    <div id="ledgerBox" style="display:none;">
        
        {{-- Report Sub-Header --}}
        <div class="card border-0 shadow-sm mb-2 rounded-3 bg-white">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2" id="ledgerHeader">
            </div>
        </div>

        {{-- COLLAPSIBLE CUSTOMER CONTAINERS TOOLBAR & LIST --}}
        <div id="customerAccordionSection">
            <div class="cust-acc-toolbar no-print" id="custAccToolbar">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold" id="customerCountBadge" style="font-size: 0.78rem;">0 Customers</span>
                    <div class="input-group input-group-sm" style="width: 260px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="customerCardSearchInput" class="form-control border-start-0" placeholder="Filter customer cards by name...">
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border fw-bold text-primary" id="btnExpandAllCustomers">
                        <i class="fas fa-angle-double-down me-1"></i> Expand All
                    </button>
                    <button type="button" class="btn btn-sm btn-light border fw-bold text-secondary" id="btnCollapseAllCustomers">
                        <i class="fas fa-angle-double-up me-1"></i> Collapse All
                    </button>
                </div>
            </div>

            <div id="customerGroupsContainer" class="no-print"></div>
        </div>

        {{-- COMBINED STATEMENT FALLBACK VIEW (For linked Customer+Vendor "Both" tab) --}}
        <div id="singleCombinedSection" style="display:none;">
            {{-- DESKTOP TABLE VIEW (d-none d-md-block) --}}
            <div class="card border-0 shadow-sm mb-3 rounded-3 bg-white d-none d-md-block">
                <div class="card-body p-0">
                    <div class="sale-table-wrap">
                        <table class="table table-bordered table-hover align-middle mb-0 report-table">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Date</th>
                                    <th style="width: 12%;">Ref / Invoice</th>
                                    <th style="width: 18%;">Customer</th>
                                    <th>Description</th>
                                    <th style="width: 12%;">Debit (Dr)</th>
                                    <th style="width: 12%;">Credit (Cr)</th>
                                    <th style="width: 14%;">Balance</th>
                                </tr>
                            </thead>
                            <tbody id="ledgerBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- MOBILE CARDS CONTAINER (d-md-none) --}}
            <div class="d-md-none no-print" id="ledgerMobileContainer">
            </div>
        </div>

        {{-- PRINT ONLY CONTAINER --}}
        <div class="d-none print-container">
            <div style="text-align: center; margin-bottom: 15px;">
                <h2 style="font-weight: 900; margin: 0; font-size: 20px; text-transform: uppercase;">{{ \App\Models\Setting::get('company_name', 'WHITE DIAMOND (PACKAGES PRIVATE LIMITED)') }}</h2>
                <p style="margin: 0; font-size: 11px;">{{ \App\Models\Setting::get('company_address', 'Hanif Garden Dry Port Road Near Soha Mall Faisalabad') }}</p>
                <p style="margin: 0; font-size: 11px;">Tel: {{ \App\Models\Setting::get('company_phone', '+92 3216293333 | +92 300 7995500') }} | Web: {{ \App\Models\Setting::get('company_website', 'whitediamondpack.com') }}</p>
                <h5 style="font-weight: 800; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase;">CUSTOMER LEDGER</h5>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 11px;">
                <div style="font-weight: bold;">
                    Account No : - <br>
                    <span id="printCustName" style="text-transform: uppercase; font-size: 12px;">ALL CUSTOMERS</span>
                </div>
                <div style="text-align: right; font-weight: bold;">
                    Date : <span id="printDateRangeVal">All</span> <br>
                    Currency : PKR <br>
                    Total Due : PKR <span id="printTotalDueVal">0.00</span>
                </div>
            </div>

            <div id="printGroupsContainer"></div>

            <table class="print-table" id="printSingleTableFallback" style="display:none;">
                <thead>
                    <tr>
                        <th class="text-left" style="width: 10%;">Date</th>
                        <th class="text-left" style="width: 15%;">Type</th>
                        <th class="text-left" style="width: 14%;">Invoice No.</th>
                        <th class="text-left" style="width: 25%;">Details</th>
                        <th class="text-right" style="width: 12%;">Debit</th>
                        <th class="text-right" style="width: 12%;">Credit</th>
                        <th class="text-right" style="width: 12%;">Balance</th>
                    </tr>
                </thead>
                <tbody id="printLedgerBody">
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            if ($('.select2').length > 0) {
                $('.select2').select2({ width: '100%' });
            }

            let allCustomers = $('.customerSelect option').clone();

            // Pre-select customer if passed via URL parameter (e.g. from Customers table action)
            let urlParams = new URLSearchParams(window.location.search);
            let qCid = urlParams.get('customer_id');
            if (qCid) {
                $('.customerSelect').val(qCid);
                if ($('.customerSelect').hasClass('select2-hidden-accessible')) {
                    $('.customerSelect').trigger('change.select2');
                }
            }

            // Sync Inputs between Desktop & Mobile
            $('.startDateInput').on('change', function() { $('.startDateInput').val($(this).val()); });
            $('.endDateInput').on('change', function() { $('.endDateInput').val($(this).val()); });
            $('.provinceLedgerInput').on('change input', function() { $('.provinceLedgerInput').val($(this).val()); });
            $('.cityLedgerInput').on('change input', function() { $('.cityLedgerInput').val($(this).val()); });
            $('.areaLedgerInput').on('change input', function() { $('.areaLedgerInput').val($(this).val()); });
            $('.zoneSelect').on('change', function() {
                let selectedZone = $(this).val();
                $('.zoneSelect').val(selectedZone);

                $('.customerSelect').empty();
                allCustomers.each(function() {
                    let optionZone = $(this).attr('data-zone');
                    if ($(this).val() === 'all' || selectedZone === "" || optionZone == selectedZone) {
                        $('.customerSelect').append($(this).clone());
                    }
                });
                $('.customerSelect').val('all').trigger('change.select2');
            });
            $('.customerSelect').on('change', function() {
                let val = $(this).val();
                $('.customerSelect').val(val);
                if ($('.customerSelect').hasClass('select2-hidden-accessible')) {
                    $('.customerSelect').trigger('change.select2');
                }
            });
            $('.quickFilterSelect').on('change', function() {
                let val = $(this).val();
                $('.quickFilterSelect').val(val);
                let today = new Date();
                let start = new Date();
                let end = new Date();

                if (val === 'daily') {
                } else if (val === 'weekly') {
                    let day = today.getDay();
                    let diff = today.getDate() - day + (day === 0 ? -6 : 1);
                    start.setDate(diff);
                } else if (val === 'monthly') {
                    start.setDate(1);
                } else if (val === 'yearly') {
                    start.setMonth(0, 1);
                } else if (val === 'custom') {
                    return;
                }

                let formatDate = (d) => {
                    let m = '' + (d.getMonth() + 1), day = '' + d.getDate(), y = d.getFullYear();
                    if (m.length < 2) m = '0' + m;
                    if (day.length < 2) day = '0' + day;
                    return [y, m, day].join('-');
                };

                $('.startDateInput').val(formatDate(start));
                $('.endDateInput').val(formatDate(end));
                loadLedger();
            });

            // Auto-load ledger on page load
            loadLedger();

            $(document).on('click', '.btnSearchTrigger', function() {
                loadLedger();
            });

            $('.btnResetTrigger').on('click', function() {
                $('.startDateInput').val('{{ date("Y-m-d") }}');
                $('.endDateInput').val('{{ date("Y-m-d") }}');
                $('.zoneSelect').val('').trigger('change');
                $('.customerSelect').val('all').trigger('change');
                $('.provinceLedgerInput, .cityLedgerInput, .areaLedgerInput').val('');
                $('.quickFilterSelect').val('custom');
                loadLedger();
            });

            function updateReportTitle(customerName) {
                let name = customerName;
                if (!name) {
                    if ($('#printCustName').length && $('#printCustName').text().trim()) {
                        name = $('#printCustName').text().trim();
                    } else if ($('.customerSelect option:selected').length) {
                        name = $('.customerSelect option:selected').text().replace(/^--\s*|\s*--$/g, '').trim();
                    }
                }
                if (!name) {
                    name = 'Customer';
                }
                let cleanName = name.replace(/[\/\\:*?"<>|]/g, ' ').replace(/\s+/g, ' ').trim();
                let finalTitle = cleanName + ' - Ledger';
                document.title = finalTitle;
                return finalTitle;
            }

            $('.btnPrintReport').on('click', function() {
                updateReportTitle();
                window.print();
            });

            window.addEventListener('beforeprint', function() {
                updateReportTitle();
            });

            function loadLedger() {
                let zid = $(".zoneSelect").val();
                let cid = $(".customerSelect").val();
                let start = $(".startDateInput").val() || '{{ date("Y-m-d") }}';
                let end = $(".endDateInput").val() || '{{ date("Y-m-d") }}';

                $("#loader").show();
                $("#ledgerBox").hide();

                $.get("{{ route('report.customer.ledger.fetch') }}", {
                    zone_id: zid,
                    customer_id: cid || 'all',
                    start_date: start,
                    end_date: end,
                    province: $('#province_ledger_desk').val() || $('#province_ledger_mob').val() || '',
                    city: $('#city_ledger_desk').val() || $('#city_ledger_mob').val() || '',
                    area: $('#area_ledger_desk').val() || $('#area_ledger_mob').val() || ''
                }, function(res) {
                    $("#loader").hide();
                    $("#ledgerBox").show();

                    let displayStart = formatDisplayDate(start);
                    let displayEnd = formatDisplayDate(end);

                    updateReportTitle(res.customer ? res.customer.customer_name : null);

                    window.currentLedgerResponse = res;
                    let bothBadge = '';
                    let navTabs = '';
                    if (res.is_both) {
                        bothBadge = `<span class="badge bg-primary text-white me-2 px-2 py-1"><i class="fas fa-sync-alt me-1"></i>Both: Customer &amp; Vendor</span>`;
                        navTabs = `
                            <ul class="nav nav-pills mt-2" id="partyLedgerTabs" role="tablist" style="font-size: 0.85rem;">
                                <li class="nav-item">
                                    <button class="nav-link active py-1 px-3 fw-bold" id="btnTabCustomerOnly" type="button"><i class="fas fa-user-tag me-1"></i>Customer Statement (Sales &amp; Receipts)</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-1 px-3 fw-bold text-primary" id="btnTabCombinedParty" type="button" style="background:#eef2ff;"><i class="fas fa-layer-group me-1"></i>Combined Consolidated Statement (Sales &amp; Purchases Net)</button>
                                </li>
                            </ul>
                        `;
                    }

                    // Build Header
                    $("#ledgerHeader").html(`
                        <div class="w-100">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">${res.customer.customer_name} ${bothBadge}</h6>
                                    <small class="text-muted">Period: <strong>${displayStart}</strong> to <strong>${displayEnd}</strong></small>
                                </div>
                                <div>
                                     <span class="badge bg-primary text-white p-2 shadow-sm font-monospace">Statement of Account</span>
                                </div>
                            </div>
                            ${navTabs}
                        </div>
                    `);

                    renderCustomerAccordionView(res, displayStart, displayEnd);
                }).fail(function() {
                    $("#loader").hide();
                    alert("Error loading report data.");
                });
            }

            function fmtMoney(num) {
                return Number(num || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function getCustomerBalanceMeta(bal) {
                let val = parseFloat(bal || 0);
                return {
                    label: val >= 0 ? 'Dr' : 'Cr',
                    cls: val >= 0 ? 'balance-positive' : 'balance-negative'
                };
            }

            function renderCustomerAccordionView(res, displayStart, displayEnd) {
                $('#singleCombinedSection').hide();
                $('#customerAccordionSection').show();

                let groups = Array.isArray(res.customer_groups) ? res.customer_groups : [];

                if (groups.length === 0 && Array.isArray(res.transactions) && res.transactions.length > 0) {
                    let td = 0, tc = 0;
                    res.transactions.forEach(t => { td += parseFloat(t.debit || 0); tc += parseFloat(t.credit || 0); });
                    groups = [{
                        customer: res.customer || { id: 0, customer_name: 'Customer', mobile: '', address: '', city: '' },
                        opening_balance: parseFloat(res.opening_balance || 0),
                        total_debit: td,
                        total_credit: tc,
                        closing_balance: parseFloat(res.closing_balance ?? res.opening_balance ?? 0),
                        entry_count: res.transactions.length,
                        transactions: res.transactions
                    }];
                }

                let grandOpening = parseFloat(res.opening_balance || 0);
                let grandDebit = parseFloat(res.total_debit ?? groups.reduce((s, g) => s + parseFloat(g.total_debit || 0), 0));
                let grandCredit = parseFloat(res.total_credit ?? groups.reduce((s, g) => s + parseFloat(g.total_credit || 0), 0));
                let grandClosing = parseFloat(res.closing_balance ?? groups.reduce((s, g) => s + parseFloat(g.closing_balance || 0), 0));

                // Top Summary Pills
                $('#pillOpeningBalance, #mobPillOpeningBalance').text('Rs ' + fmtMoney(grandOpening));
                $('#pillTotalDebit, #mobPillTotalDebit').text('Rs ' + fmtMoney(grandDebit));
                $('#pillTotalCredit, #mobPillTotalCredit').text('Rs ' + fmtMoney(grandCredit));
                $('#pillClosingBalance, #mobPillClosingBalance').text('Rs ' + fmtMoney(Math.abs(grandClosing)) + ' ' + (grandClosing >= 0 ? 'Dr' : 'Cr'));

                $('#customerCountBadge').text(groups.length + (groups.length === 1 ? ' Customer' : ' Customers'));
                $('#custAccToolbar').toggle(groups.length > 1 || res.mode === 'all');

                if (groups.length === 0) {
                    $('#customerGroupsContainer').html(`
                        <div class="card border-0 shadow-sm rounded-3 bg-white p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i>
                            <div class="fw-bold">No customer ledger records found for this period.</div>
                        </div>
                    `);
                    $('#printGroupsContainer').html('');
                    return;
                }

                let autoExpand = (res.mode === 'single' || groups.length === 1);
                let cardsHtml = '';

                groups.forEach((g, idx) => {
                    let c = g.customer || {};
                    let cName = c.customer_name || 'Customer';
                    let cPhone = c.mobile || '';
                    let cLoc = [c.address, c.city].filter(Boolean).join(', ');
                    let openBal = parseFloat(g.opening_balance || 0);
                    let totDr = parseFloat(g.total_debit || 0);
                    let totCr = parseFloat(g.total_credit || 0);
                    let closeBal = parseFloat(g.closing_balance || 0);
                    let closeMeta = getCustomerBalanceMeta(closeBal);
                    let openMeta = getCustomerBalanceMeta(openBal);

                    let subParts = [];
                    if (cPhone) subParts.push(`<i class="fas fa-phone-alt me-1"></i>${escapeHtml(cPhone)}`);
                    if (cLoc) subParts.push(`<i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(cLoc)}`);

                    cardsHtml += `
                        <div class="cust-acc-card cust-theme-${idx % 6} ${autoExpand ? 'is-open' : ''}" data-group-idx="${idx}" data-cust-search="${escapeHtml((cName + ' ' + cPhone + ' ' + cLoc).toLowerCase())}">
                            <div class="cust-acc-header">
                                <div class="cust-acc-left">
                                    <div class="cust-acc-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div>
                                        <div class="cust-acc-title-row">
                                            <span class="cust-acc-name">${escapeHtml(cName)}</span>
                                            <span class="cust-acc-chip chip-entries">${g.entry_count || 0} ${(g.entry_count || 0) === 1 ? 'Entry' : 'Entries'}</span>
                                            <span class="cust-acc-chip">Opening: Rs ${fmtMoney(Math.abs(openBal))} ${openMeta.label}</span>
                                        </div>
                                        ${subParts.length ? `<div class="cust-acc-sub">${subParts.join(' &nbsp;|&nbsp; ')}</div>` : ''}
                                    </div>
                                </div>
                                <div class="cust-acc-right">
                                    <div class="cust-kpi-box kpi-debit">
                                        <div class="cust-kpi-val text-danger">Rs ${fmtMoney(totDr)}</div>
                                        <div class="cust-kpi-lbl">DEBIT (DR)</div>
                                    </div>
                                    <div class="cust-kpi-box kpi-credit">
                                        <div class="cust-kpi-val text-success">Rs ${fmtMoney(totCr)}</div>
                                        <div class="cust-kpi-lbl">CREDIT (CR)</div>
                                    </div>
                                    <div class="cust-kpi-box kpi-closing">
                                        <div class="cust-kpi-val ${closeMeta.cls}">Rs ${fmtMoney(Math.abs(closeBal))} <small style="font-size:0.72em;">${closeMeta.label}</small></div>
                                        <div class="cust-kpi-lbl">CLOSING BAL</div>
                                    </div>
                                    <button type="button" class="cust-acc-chevron" aria-label="Toggle customer ledger">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="cust-acc-body" style="${autoExpand ? 'display:block;' : 'display:none;'}">
                                <div class="cust-acc-inner-bar">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold btn-print-single-customer" data-group-idx="${idx}" style="font-size: 0.74rem;">
                                            <i class="fas fa-print me-1"></i> Print Customer
                                        </button>
                                    </div>
                                    <div class="input-group input-group-sm" style="width: 260px;">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control border-start-0 cust-inner-search" data-group-idx="${idx}" placeholder="Live search in ${escapeHtml(cName)}...">
                                    </div>
                                </div>

                                {{-- Desktop Table --}}
                                <div class="cust-acc-table-wrap d-none d-md-block">
                                    <table class="table table-hover align-middle mb-0 cust-acc-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 11%;">Date</th>
                                                <th style="width: 15%;">Ref / Invoice</th>
                                                <th>Description</th>
                                                <th class="text-end" style="width: 13%;">Debit (Dr)</th>
                                                <th class="text-end" style="width: 13%;">Credit (Cr)</th>
                                                <th class="text-end" style="width: 15%;">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="custGroupTableBody_${idx}"></tbody>
                                    </table>
                                </div>

                                {{-- Mobile Rows --}}
                                <div class="d-md-none" id="custGroupMobileBody_${idx}"></div>
                            </div>
                        </div>
                    `;
                });

                $('#customerGroupsContainer').html(cardsHtml);
                groups.forEach((g, idx) => renderCustomerGroupRows(idx, res));
                populateAllCustomerGroupsPrintLayout(res, groups, displayStart, displayEnd);
            }

            function renderCustomerGroupRows(idx, res) {
                let groups = (res && res.customer_groups) ? res.customer_groups : [];
                let g = groups[idx];
                if (!g) return;

                let searchQ = ($('.cust-inner-search[data-group-idx="' + idx + '"]').val() || '').toLowerCase().trim();
                let allTx = Array.isArray(g.transactions) ? g.transactions : [];
                let visibleTx = allTx.filter(t => {
                    if (!searchQ) return true;
                    let hay = ((t.date || '') + ' ' + (t.invoice || '') + ' ' + (t.description || '')).toLowerCase();
                    return hay.includes(searchQ);
                });

                let openBal = parseFloat(g.opening_balance || 0);
                let openMeta = getCustomerBalanceMeta(openBal);

                let deskHtml = `
                    <tr class="bg-light fw-bold">
                        <td class="text-nowrap text-muted">-</td>
                        <td>-</td>
                        <td>Opening Balance (B/F)</td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end ${openMeta.cls}">Rs ${fmtMoney(Math.abs(openBal))} <small class="text-muted">${openMeta.label}</small></td>
                    </tr>
                `;

                let mobHtml = `
                    <div class="mob-card p-2 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-dark" style="font-size: 12px;">Opening Balance (B/F)</strong>
                            <strong class="${openMeta.cls}" style="font-size: 12.5px;">Rs ${fmtMoney(Math.abs(openBal))} <small>${openMeta.label}</small></strong>
                        </div>
                    </div>
                `;

                if (visibleTx.length === 0) {
                    deskHtml += `
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No matching transactions found.</td>
                        </tr>
                    `;
                }

                visibleTx.forEach(t => {
                    let debit = t.debit && t.debit > 0 ? parseFloat(t.debit) : 0;
                    let credit = t.credit && t.credit > 0 ? parseFloat(t.credit) : 0;
                    let bal = parseFloat(t.balance || 0);
                    let meta = getCustomerBalanceMeta(bal);

                    let invoiceBadge = t.invoice_url
                        ? `<a href="${t.invoice_url}" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-decoration-none py-1 px-2 shadow-sm" title="View & Print Invoice"><i class="fas fa-file-invoice me-1"></i>${escapeHtml(t.invoice)} <i class="fas fa-external-link-alt ms-1" style="font-size: 8.5px;"></i></a>`
                        : `<span class="badge bg-light text-primary border font-monospace">${escapeHtml(t.invoice ?? '-')}</span>`;

                    let debitDisplay = debit > 0 ? 'Rs ' + fmtMoney(debit) : '-';
                    let creditDisplay = credit > 0 ? 'Rs ' + fmtMoney(credit) : '-';

                    deskHtml += `
                        <tr>
                            <td class="text-nowrap small">${escapeHtml(t.date)}</td>
                            <td>${invoiceBadge}</td>
                            <td>${escapeHtml(t.description || '')}</td>
                            <td class="text-end text-danger fw-semibold">${debitDisplay}</td>
                            <td class="text-end text-success fw-semibold">${creditDisplay}</td>
                            <td class="text-end fw-bold ${meta.cls}">Rs ${fmtMoney(Math.abs(bal))} <small class="text-muted" style="font-size:0.75em">${meta.label}</small></td>
                        </tr>
                    `;

                    mobHtml += `
                        <div class="mob-card p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                ${invoiceBadge}
                                <small class="text-muted" style="font-size: 10.5px;">${escapeHtml(t.date)}</small>
                            </div>
                            <div class="mb-1">
                                <small class="text-muted d-block" style="font-size: 11px;">${escapeHtml(t.description || '')}</small>
                            </div>
                            <div class="border-top pt-2 mt-1">
                                <div class="row g-1 text-center" style="font-size: 11px;">
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Debit (Dr)</span>
                                        <strong class="text-danger">${debitDisplay}</strong>
                                    </div>
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Credit (Cr)</span>
                                        <strong class="text-success">${creditDisplay}</strong>
                                    </div>
                                    <div class="col-4">
                                        <span class="text-muted d-block" style="font-size: 10px;">Balance</span>
                                        <strong class="${meta.cls}">Rs ${fmtMoney(Math.abs(bal))} <small>${meta.label}</small></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                let closeBal = parseFloat(g.closing_balance || 0);
                let closeMeta = getCustomerBalanceMeta(closeBal);
                deskHtml += `
                    <tr class="fw-bold bg-light">
                        <td colspan="3" class="text-end text-dark">Customer Totals:</td>
                        <td class="text-end text-danger">Rs ${fmtMoney(g.total_debit)}</td>
                        <td class="text-end text-success">Rs ${fmtMoney(g.total_credit)}</td>
                        <td class="text-end ${closeMeta.cls}">Rs ${fmtMoney(Math.abs(closeBal))} <small class="text-muted" style="font-size:0.75em">${closeMeta.label}</small></td>
                    </tr>
                `;

                $('#custGroupTableBody_' + idx).html(deskHtml);
                $('#custGroupMobileBody_' + idx).html(mobHtml);
            }

            function populateAllCustomerGroupsPrintLayout(res, groups, displayStart, displayEnd) {
                let cTitle = groups.length === 1 ? (groups[0].customer?.customer_name || 'CUSTOMER') : ('ALL CUSTOMERS (' + groups.length + ')');
                $('#printCustName').text(cTitle);
                $('#printDateRangeVal').text(displayStart + ' to ' + displayEnd);
                let grandClosing = groups.reduce((s, g) => s + parseFloat(g.closing_balance || 0), 0);
                $('#printTotalDueVal').text(fmtMoney(grandClosing));
                $('#printSingleTableFallback').hide();

                let html = '';
                groups.forEach(g => {
                    html += buildSingleCustomerGroupPrintHtml(g);
                });
                $('#printGroupsContainer').html(html);
            }

            function buildSingleCustomerGroupPrintHtml(g) {
                let cName = g.customer?.customer_name || 'Customer';
                let openBal = parseFloat(g.opening_balance || 0);
                let closeBal = parseFloat(g.closing_balance || 0);

                let rows = `
                    <tr>
                        <td class="text-left fw-bold">-</td>
                        <td class="text-left fw-bold">Opening Balance</td>
                        <td class="text-center fw-bold">-</td>
                        <td class="text-left fw-bold">Opening Balance (B/F)</td>
                        <td class="text-right fw-bold">-</td>
                        <td class="text-right fw-bold">-</td>
                        <td class="text-right fw-bold">${fmtMoney(openBal)}</td>
                    </tr>
                `;

                (g.transactions || []).forEach(t => {
                    let debit = t.debit && t.debit > 0 ? parseFloat(t.debit) : 0;
                    let credit = t.credit && t.credit > 0 ? parseFloat(t.credit) : 0;
                    let bal = parseFloat(t.balance || 0);

                    let type = 'Journal Entry';
                    let refDesc = t.description || '';
                    if (refDesc.toLowerCase().includes('payment') || refDesc.toLowerCase().includes('receipt')) {
                        type = 'Payment Received';
                    } else if (refDesc.toLowerCase().includes('sale invoice')) {
                        type = 'Sale Invoice';
                    }

                    let acMatch = refDesc.match(/\[A\/C:\s*([^\]]+)\]/);
                    if (acMatch) refDesc = refDesc.replace(acMatch[0], '').trim();

                    let invNo = (t.invoice && t.invoice !== '-') ? t.invoice : '-';

                    rows += `
                        <tr>
                            <td class="text-left">${escapeHtml(t.date || '-')}</td>
                            <td class="text-left">${escapeHtml(type)}</td>
                            <td class="text-left font-monospace">${escapeHtml(invNo)}</td>
                            <td class="text-left">${escapeHtml(refDesc)}</td>
                            <td class="text-right">${debit > 0 ? fmtMoney(debit) : '-'}</td>
                            <td class="text-right">${credit > 0 ? fmtMoney(credit) : '-'}</td>
                            <td class="text-right">${fmtMoney(bal)}</td>
                        </tr>
                    `;
                });

                rows += `
                    <tr>
                        <td colspan="4" class="text-right fw-bold">Total:</td>
                        <td class="text-right fw-bold">${fmtMoney(g.total_debit)}</td>
                        <td class="text-right fw-bold">${fmtMoney(g.total_credit)}</td>
                        <td class="text-right fw-bold">${fmtMoney(closeBal)}</td>
                    </tr>
                `;

                return `
                    <div style="margin-bottom: 18px; page-break-inside: avoid;">
                        <div style="font-weight: 800; font-size: 13px; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 4px; display: flex; justify-content: space-between;">
                            <span>${escapeHtml(cName)}</span>
                            <span>Closing: PKR ${fmtMoney(Math.abs(closeBal))} ${closeBal >= 0 ? 'Dr' : 'Cr'}</span>
                        </div>
                        <table class="print-table">${rows}</table>
                    </div>
                `;
            }

            // Accordion Header Toggle
            $(document).on('click', '.cust-acc-header', function() {
                let $card = $(this).closest('.cust-acc-card');
                let $body = $card.find('> .cust-acc-body');
                $card.toggleClass('is-open');
                $body.slideToggle(180);
            });

            // Expand All / Collapse All
            $(document).on('click', '#btnExpandAllCustomers', function() {
                $('#customerGroupsContainer .cust-acc-card:visible').each(function() {
                    $(this).addClass('is-open').find('> .cust-acc-body').slideDown(150);
                });
            });

            $(document).on('click', '#btnCollapseAllCustomers', function() {
                $('#customerGroupsContainer .cust-acc-card').each(function() {
                    $(this).removeClass('is-open').find('> .cust-acc-body').slideUp(150);
                });
            });

            // Filter Customer Containers by Name/Phone/Location
            $(document).on('input', '#customerCardSearchInput', function() {
                let q = ($(this).val() || '').toLowerCase().trim();
                let visibleCount = 0;
                $('#customerGroupsContainer .cust-acc-card').each(function() {
                    let hay = $(this).data('cust-search') || '';
                    let match = !q || hay.includes(q);
                    $(this).toggle(match);
                    if (match) visibleCount++;
                });
                $('#customerCountBadge').text(visibleCount + (visibleCount === 1 ? ' Customer' : ' Customers'));
            });

            // Live search inside individual customer card
            $(document).on('input', '.cust-inner-search', function() {
                let idx = $(this).data('group-idx');
                if (window.currentLedgerResponse) {
                    renderCustomerGroupRows(idx, window.currentLedgerResponse);
                }
            });

            // Print Single Customer from inside its container
            $(document).on('click', '.btn-print-single-customer', function(e) {
                e.stopPropagation();
                let idx = $(this).data('group-idx');
                let res = window.currentLedgerResponse;
                if (!res || !res.customer_groups || !res.customer_groups[idx]) return;
                let g = res.customer_groups[idx];
                $('#printCustName').text(g.customer?.customer_name || 'CUSTOMER');
                $('#printTotalDueVal').text(fmtMoney(g.closing_balance));
                $('#printGroupsContainer').html(buildSingleCustomerGroupPrintHtml(g));
                updateReportTitle(g.customer?.customer_name);
                window.print();
                setTimeout(function() {
                    let start = $('.startDateInput').val() || '{{ date("Y-m-d") }}';
                    let end = $('.endDateInput').val() || '{{ date("Y-m-d") }}';
                    populateAllCustomerGroupsPrintLayout(res, res.customer_groups, formatDisplayDate(start), formatDisplayDate(end));
                }, 500);
            });

            function formatDisplayDate(dateStr) {
                if (!dateStr) return '-';
                let d = new Date(dateStr);
                let months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                return d.getDate().toString().padStart(2, '0') + '-' + months[d.getMonth()] + '-' + d.getFullYear();
            }
            // Toggle between Customer Ledger and Combined Consolidated Ledger
            $(document).on('click', '#btnTabCustomerOnly', function() {
                $('#partyLedgerTabs button').removeClass('active');
                $(this).addClass('active');
                if (window.currentLedgerResponse) {
                    let start = $('.startDateInput').val() || '{{ date("Y-m-d") }}';
                    let end = $('.endDateInput').val() || '{{ date("Y-m-d") }}';
                    renderCustomerAccordionView(window.currentLedgerResponse, formatDisplayDate(start), formatDisplayDate(end));
                }
            });

            $(document).on('click', '#btnTabCombinedParty', function() {
                $('#partyLedgerTabs button').removeClass('active');
                $(this).addClass('active');
                if (window.currentLedgerResponse && window.currentLedgerResponse.combined_ledger) {
                    $('#customerAccordionSection').hide();
                    $('#singleCombinedSection').show();
                    renderCombinedView(window.currentLedgerResponse.combined_ledger);
                }
            });

            function renderCombinedView(combined) {
                let lastBalance = parseFloat(combined.opening_balance || 0);
                let totalDebit = 0;
                let totalCredit = 0;

                let formattedOpening = 'Rs ' + Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ' + (lastBalance >= 0 ? '(Dr - Lene)' : '(Cr - Dene)');
                $('#pillOpeningBalance, #mobPillOpeningBalance').text(formattedOpening);

                let html = `
                    <tr class="bg-light fw-bold">
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center"><span class="badge bg-secondary">Combined</span></td>
                        <td class="text-start">Net Opening Balance (B/F)</td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end text-dark">
                            Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} <small class="text-muted">${lastBalance >= 0 ? 'Dr (Lene)' : 'Cr (Dene)'}</small>
                        </td>
                    </tr>
                `;

                let mobHtml = `
                    <div class="mob-card p-2.5 p-2 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-dark" style="font-size: 12.5px;">Net Opening Balance (B/F)</strong>
                            <strong class="text-dark" style="font-size: 13px;">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} ${lastBalance >= 0 ? 'Dr' : 'Cr'}</strong>
                        </div>
                    </div>
                `;

                (combined.transactions || []).forEach(t => {
                    let debit = t.debit && t.debit > 0 ? parseFloat(t.debit) : 0;
                    let credit = t.credit && t.credit > 0 ? parseFloat(t.credit) : 0;
                    totalDebit += debit;
                    totalCredit += credit;
                    lastBalance = parseFloat(t.balance);

                    let balLabel = lastBalance >= 0 ? 'Dr (Lene hain)' : 'Cr (Dene hain)';
                    let balClass = lastBalance >= 0 ? 'balance-positive' : 'balance-negative';
                    let typeBadge = t.type === 'Customer' ? '<span class="badge bg-primary">Sale / Cust</span>' : '<span class="badge bg-warning text-dark">Purch / Vend</span>';

                    html += `
                        <tr>
                            <td class="text-center small text-nowrap">${t.date}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">${t.ref_no}</span></td>
                            <td class="text-center">${typeBadge}</td>
                            <td class="text-start">${t.description}</td>
                            <td class="text-end text-danger fw-semibold">${debit > 0 ? 'Rs ' + debit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                            <td class="text-end text-success fw-semibold">${credit > 0 ? 'Rs ' + credit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                            <td class="text-end fw-bold ${balClass}">
                                Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} 
                                <small class="d-block text-muted" style="font-size:0.75em">${balLabel}</small>
                            </td>
                        </tr>
                    `;

                    mobHtml += `
                        <div class="mob-card p-2.5 p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>${typeBadge} <span class="badge bg-light text-dark border ms-1">${t.ref_no}</span></div>
                                <small class="text-muted" style="font-size: 10.5px;">${t.date}</small>
                            </div>
                            <div class="mb-1">
                                <small class="text-muted d-block" style="font-size: 11px;">${t.description}</small>
                            </div>
                            <div class="border-top pt-2 mt-1">
                                <div class="row g-1 text-center" style="font-size: 11px;">
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Debit (Dr)</span>
                                        <strong class="text-danger">${debit > 0 ? 'Rs ' + debit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</strong>
                                    </div>
                                    <div class="col-4 border-end">
                                        <span class="text-muted d-block" style="font-size: 10px;">Credit (Cr)</span>
                                        <strong class="text-success">${credit > 0 ? 'Rs ' + credit.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</strong>
                                    </div>
                                    <div class="col-4">
                                        <span class="text-muted d-block" style="font-size: 10px;">Net Balance</span>
                                        <strong class="${balClass}">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} <small>${lastBalance >= 0 ? 'Dr' : 'Cr'}</small></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += `
                    <tr class="fw-bold bg-light">
                        <td colspan="4" class="text-end text-dark">Combined Totals:</td>
                        <td class="text-end text-danger">Rs ${totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-success">Rs ${totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end ${lastBalance >= 0 ? 'balance-positive' : 'balance-negative'}">Rs ${Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2})} ${lastBalance >= 0 ? 'Dr (Lene)' : 'Cr (Dene)'}</td>
                    </tr>
                `;

                $("#ledgerBody").html(html);
                $("#ledgerMobileContainer").html(mobHtml);

                $('#pillTotalDebit, #mobPillTotalDebit').text('Rs ' + totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2}));
                $('#pillTotalCredit, #mobPillTotalCredit').text('Rs ' + totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2}));
                $('#pillClosingBalance, #mobPillClosingBalance').text('Rs ' + Math.abs(lastBalance).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ' + (lastBalance >= 0 ? 'Dr (Lene)' : 'Cr (Dene)'));
            }

        });
    </script>
@include('admin_panel.partials.location_selector_script')
<script>
    $(document).ready(function() {
        setupLocationCascade({
            province: '.provinceLedgerInput',
            city: '.cityLedgerInput',
            area: '.areaLedgerInput',
            customer: '.customerSelect'
        });
    });
</script>
@endsection
