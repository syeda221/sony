<?php

// 1. UPDATE SaleController.php
$scPath = __DIR__ . '/../app/Http/Controllers/SaleController.php';
$sc = file_get_contents($scPath);

// Eager load in saleinvoice
$target = "Sale::with(['customer_relation.salesOfficer'])->findOrFail(\$id);";
$replace = "Sale::with(['customer_relation.salesOfficer', 'installmentPlan.installments', 'installmentPlan.advanceAccount'])->findOrFail(\$id);";
if (strpos($sc, $target) !== false) {
    $sc = str_replace($target, $replace, $sc);
    echo "SaleController::saleinvoice eager load updated\n";
}

// Fix $currentBalance in saleinvoice
$target = '$currentBalance = $previousBalance + $sale->total_net;';
$replace = '$currentBalance = $previousBalance + $sale->total_net - (float)($sale->cash ?? 0);';
if (strpos($sc, $target) !== false) {
    $sc = str_replace($target, $replace, $sc);
    echo "SaleController::saleinvoice currentBalance calculation updated\n";
}

// Eager load in saleedit
$target = "Sale::with(['items.product.warehouseStocks', 'customer_relation'])->findOrFail(\$id);";
$replace = "Sale::with(['items.product.warehouseStocks', 'customer_relation', 'installmentPlan.installments', 'installmentPlan.advanceAccount'])->findOrFail(\$id);";
if (strpos($sc, $target) !== false) {
    $sc = str_replace($target, $replace, $sc);
    echo "SaleController::saleedit eager load updated\n";
}

// ProcessSale delete plan if unchecked
$target = '} catch (\Exception $e) {
                    \Log::error(\'Sale Installment Creation Error: \' . $e->getMessage());
                }
            }

            // If AJAX/JSON response needed';

$replace = '} catch (\Exception $e) {
                    \Log::error(\'Sale Installment Creation Error: \' . $e->getMessage());
                }
            } else {
                if ($sale->installmentPlan) {
                    $sale->installmentPlan->delete();
                }
            }

            // If AJAX/JSON response needed';

if (strpos($sc, $target) !== false) {
    $sc = str_replace($target, $replace, $sc);
    echo "SaleController::processSale plan deletion on uncheck added\n";
}

file_put_contents($scPath, $sc);


// 2. UPDATE saleinvoice.blade.php
$siPath = __DIR__ . '/../resources/views/admin_panel/sale/saleinvoice.blade.php';
$si = file_get_contents($siPath);

$targetSi = '                        <!-- Grand Total Highlight Row -->
                        <tr class="total-row">
                            <td class="lbl">
                                {{ $finalPayable < 0 ? \'REFUND TO CUSTOMER\' : \'GRAND TOTAL\' }}
                            </td>
                            <td class="val">
                                Rs. {{ number_format(abs($finalPayable), 2) }}
                            </td>
                        </tr>

                        @if ($showCustomerBalance && !empty($sale->customer_id))
                            @php
                                $prevBal = (float)($previousBalance ?? 0);
                                $totalClosingBal = (float)($currentBalance ?? ($prevBal + $finalPayable - $paidAmount));
                            @endphp
                            @if ($paidAmount > 0)
                                <tr>
                                    <td class="lbl text-success">Paid / Received</td>
                                    <td class="val text-success font-monospace">- Rs. {{ number_format($paidAmount, 2) }}</td>
                                </tr>
                            @endif
                            <tr style="border-top: 1px solid #cbd5e1;">
                                <td class="lbl text-muted">Previous Balance</td>
                                <td class="val text-dark font-monospace">
                                    Rs. {{ number_format(abs($prevBal), 2) }} {{ $prevBal >= 0 ? \'Dr\' : \'Cr\' }}
                                </td>
                            </tr>
                            <tr style="background: #f8fafc; border-top: 1.5px solid var(--primary-navy);">
                                <td class="lbl fw-bold" style="font-size: 12px; color: var(--primary-navy);">
                                    Total Balance
                                </td>
                                <td class="val fw-bold font-monospace" style="font-size: 13.5px; color: {{ $totalClosingBal >= 0 ? \'#b91c1c\' : \'#15803d\' }};">
                                    Rs. {{ number_format(abs($totalClosingBal), 2) }} {{ $totalClosingBal >= 0 ? \'Dr\' : \'Cr\' }}
                                </td>
                            </tr>
                        @endif';

$replaceSi = '                        <!-- Grand Total Highlight Row -->
                        <tr class="total-row">
                            <td class="lbl">
                                {{ $finalPayable < 0 ? \'REFUND TO CUSTOMER\' : \'GRAND TOTAL\' }}
                            </td>
                            <td class="val">
                                Rs. {{ number_format(abs($finalPayable), 2) }}
                            </td>
                        </tr>

                        {{-- Installment / Payment Summary Rows --}}
                        @if ($sale->installmentPlan)
                            <tr style="border-top: 1.5px solid #a855f7;">
                                <td class="lbl text-success fw-bold">Advance Paid / Received</td>
                                <td class="val text-success font-monospace fw-bold">- Rs. {{ number_format($sale->installmentPlan->advance_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="lbl fw-bold" style="color: #7e22ce;">Remaining (Financed)</td>
                                <td class="val fw-bold font-monospace" style="color: #7e22ce;">Rs. {{ number_format($sale->installmentPlan->financed_amount, 2) }}</td>
                            </tr>
                            <tr style="background: #faf5ff;">
                                <td class="lbl" style="color: #6b21a8; font-size: 11px;">Monthly Installment</td>
                                <td class="val font-monospace" style="color: #6b21a8; font-weight: 700; font-size: 11px;">
                                    Rs. {{ number_format($sale->installmentPlan->monthly_installment, 2) }} / Mo ({{ $sale->installmentPlan->duration_months }} Mos)
                                </td>
                            </tr>
                        @elseif ($paidAmount > 0)
                            <tr>
                                <td class="lbl text-success">Paid / Received</td>
                                <td class="val text-success font-monospace">- Rs. {{ number_format($paidAmount, 2) }}</td>
                            </tr>
                            @php
                                $billBalanceDue = max(0, $finalPayable - $paidAmount);
                            @endphp
                            @if ((!$showCustomerBalance || empty($sale->customer_id)) && $billBalanceDue > 0)
                                <tr style="border-top: 1px solid #cbd5e1;">
                                    <td class="lbl fw-bold text-danger">Balance Due</td>
                                    <td class="val fw-bold font-monospace text-danger">Rs. {{ number_format($billBalanceDue, 2) }}</td>
                                </tr>
                            @endif
                        @endif

                        @if ($showCustomerBalance && !empty($sale->customer_id))
                            @php
                                $prevBal = (float)($previousBalance ?? 0);
                                $totalClosingBal = (float)($currentBalance ?? ($prevBal + $finalPayable - $paidAmount));
                            @endphp
                            <tr style="border-top: 1px solid #cbd5e1;">
                                <td class="lbl text-muted">Previous Balance</td>
                                <td class="val text-dark font-monospace">
                                    Rs. {{ number_format(abs($prevBal), 2) }} {{ $prevBal >= 0 ? \'Dr\' : \'Cr\' }}
                                </td>
                            </tr>
                            <tr style="background: #f8fafc; border-top: 1.5px solid var(--primary-navy);">
                                <td class="lbl fw-bold" style="font-size: 12px; color: var(--primary-navy);">
                                    Total Balance
                                </td>
                                <td class="val fw-bold font-monospace" style="font-size: 13.5px; color: {{ $totalClosingBal >= 0 ? \'#b91c1c\' : \'#15803d\' }};">
                                    Rs. {{ number_format(abs($totalClosingBal), 2) }} {{ $totalClosingBal >= 0 ? \'Dr\' : \'Cr\' }}
                                </td>
                            </tr>
                        @endif';

// Normalise line endings for comparison
$siNormalized = str_replace("\r\n", "\n", $si);
$targetSiNormalized = str_replace("\r\n", "\n", $targetSi);
$replaceSiNormalized = str_replace("\r\n", "\n", $replaceSi);

if (strpos($siNormalized, $targetSiNormalized) !== false) {
    $siNormalized = str_replace($targetSiNormalized, $replaceSiNormalized, $siNormalized);
    file_put_contents($siPath, $siNormalized);
    echo "saleinvoice.blade.php summary table updated successfully\n";
} else {
    echo "saleinvoice.blade.php target NOT FOUND\n";
}

// 3. UPDATE edit_sale.blade.php
$esPath = __DIR__ . '/../resources/views/admin_panel/sale/edit_sale.blade.php';
$es = file_get_contents($esPath);
$es = str_replace("\r\n", "\n", $es);

// Update account defaultVal
$accTarget = '@php
                                        $accTitleLower = strtolower($acc->title);
                                        $defaultVal = str_contains($accTitleLower, \'cash\') && ($sale->cash ?? 0) > 0 ? (float)$sale->cash : \'\';
                                    @endphp';

$accReplace = '@php
                                        $accTitleLower = strtolower($acc->title);
                                        if ($sale->installmentPlan) {
                                            $defaultVal = ($sale->installmentPlan->advance_account_id == $acc->id) ? (float)$sale->installmentPlan->advance_amount : (
                                                (!$sale->installmentPlan->advance_account_id && str_contains($accTitleLower, \'cash\')) ? (float)$sale->installmentPlan->advance_amount : \'\'
                                            );
                                        } else {
                                            $defaultVal = str_contains($accTitleLower, \'cash\') && ($sale->cash ?? 0) > 0 ? (float)$sale->cash : \'\';
                                        }
                                    @endphp';

$accTargetNorm = str_replace("\r\n", "\n", $accTarget);
$accReplaceNorm = str_replace("\r\n", "\n", $accReplace);
if (strpos($es, $accTargetNorm) !== false) {
    $es = str_replace($accTargetNorm, $accReplaceNorm, $es);
    echo "edit_sale.blade.php account prefill updated\n";
} else {
    echo "edit_sale.blade.php account prefill NOT FOUND\n";
}

// Insert panel_installment_plan after panel_cheque
$chequeEndTarget = '                                <small class="text-muted" style="font-size: 10.5px;">Jis din bank mein lagana / cash hona hai</small>
                                </div>
                            </div>
                        </div>';

$installmentPanelHtml = '                                <small class="text-muted" style="font-size: 10.5px;">Jis din bank mein lagana / cash hona hai</small>
                                </div>
                            </div>
                        </div>

                        {{-- Panel 3: Dedicated Installment Plan Details --}}
                        <div id="panel_installment_plan" class="p-3 rounded-3 border transition-all mb-2 shadow-sm" style="background: #FDF4FF; border: 1.5px solid #C084FC !important;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle" style="width: 36px; height: 36px; background: #F3E8FF; color: #9333EA;">
                                        <i class="fas fa-calendar-alt fs-5"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size: 13.5px;">Sale Installment Plan</span>
                                        <small class="text-muted" style="font-size: 11px;">1 to 12 months flexible installment &amp; subscription plan</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="is_installment_plan" value="1" id="toggleInstallmentPlan" {{ $sale->installmentPlan ? \'checked\' : \'\' }} style="width: 2.4em; height: 1.3em; cursor: pointer;">
                                </div>
                            </div>

                            @php
                                $hasPlan = !empty($sale->installmentPlan);
                                $planDuration = $hasPlan ? (int)$sale->installmentPlan->duration_months : 12;
                                $planAdvance = $hasPlan ? (float)$sale->installmentPlan->advance_amount : 0;
                                $planAdvAccId = $hasPlan ? $sale->installmentPlan->advance_account_id : null;
                                $planStartDate = $hasPlan && $sale->installmentPlan->start_date ? $sale->installmentPlan->start_date->format(\'Y-m-d\') : date(\'Y-m-d\', strtotime(\'+1 month\'));
                            @endphp

                            {{-- Collapsible Installment Details --}}
                            <div id="installmentPlanDetails" style="{{ $hasPlan ? \'display: block;\' : \'display: none;\' }}" class="pt-3 border-top mt-3">
                                {{-- Duration Selection: 1 to 12 Months --}}
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label mb-0 fw-bold text-dark" style="font-size: 12px;">
                                            <i class="fas fa-clock text-purple me-1" style="color: #9333EA;"></i> Plan Duration (Months)
                                        </label>
                                        <span class="badge text-white px-2 py-1" id="badgeSelectedMonths" style="background-color: #9333EA; font-size: 11px;">{{ $planDuration }} Months Selected</span>
                                    </div>
                                    
                                    {{-- 12-Month Segmented Grid (All 12 fit in 1 single row) --}}
                                    <div class="installment-months-grid mb-2" id="installmentMonthsPills">
                                        @for($m = 1; $m <= 12; $m++)
                                            <button type="button" class="btn btn-month-pill {{ $m == $planDuration ? \'active\' : \'\' }}" data-months="{{ $m }}">
                                                <span class="pill-number">{{ $m }}</span>
                                                <span class="pill-unit">{{ $m == 1 ? \'Mo\' : \'Mos\' }}</span>
                                            </button>
                                        @endfor
                                    </div>
                                    <input type="hidden" name="installment_duration_months" id="installmentDurationMonths" value="{{ $planDuration }}">
                                </div>

                                <div class="row g-2 mb-3">
                                    {{-- Down Payment / Advance Amount --}}
                                    <div class="col-md-4 col-12">
                                        <label class="form-label mb-1 fw-bold text-dark" style="font-size: 12px;">
                                            <i class="fas fa-hand-holding-usd text-success me-1"></i> Down Payment / Advance (Rs.)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white fw-bold">Rs.</span>
                                            <input type="number" step="0.01" class="form-control fw-bold text-end" name="installment_advance_amount" id="installmentAdvanceAmount" placeholder="0.00" value="{{ $planAdvance > 0 ? $planAdvance : 0 }}" style="font-size: 14px; height: 36px; border: 1.5px solid #cbd5e1;">
                                        </div>
                                        <div class="d-flex gap-1 mt-1">
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 10px;" id="btnAdvZero">Zero Adv.</button>
                                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 10px;" id="btnAdv25">25% Adv.</button>
                                            <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 10px;" id="btnAdv50">50% Adv.</button>
                                        </div>
                                    </div>

                                    {{-- Advance Deposit Account --}}
                                    <div class="col-md-4 col-12">
                                        <label class="form-label mb-1 fw-bold text-dark" style="font-size: 12px;">
                                            <i class="fas fa-university text-primary me-1"></i> Advance Deposit Account
                                        </label>
                                        <select class="form-select form-select-sm fw-bold" name="installment_advance_account_id" id="installmentAdvanceAccountId" style="height: 36px; font-size: 12.5px;">
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}" {{ ($planAdvAccId == $acc->id || (!$planAdvAccId && str_contains(strtolower($acc->title), \'cash\'))) ? \'selected\' : \'\' }}>
                                                    {{ $acc->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted" style="font-size: 10.5px;">Account receiving down payment</small>
                                    </div>

                                    {{-- First Installment Due Date --}}
                                    <div class="col-md-4 col-12">
                                        <label class="form-label mb-1 fw-bold text-dark" style="font-size: 12px;">
                                            <i class="fas fa-calendar-day text-info me-1"></i> First Due Date
                                        </label>
                                        <input type="date" class="form-control form-control-sm" name="installment_start_date" id="installmentStartDate" value="{{ $planStartDate }}" style="font-size: 13px; height: 36px;">
                                        <small class="text-muted" style="font-size: 10.5px;">First installment due date</small>
                                    </div>
                                </div>

                                {{-- Live Calculated Highlight Card --}}
                                <div class="p-3 rounded-3 mb-3 text-white shadow-sm" style="background: linear-gradient(135deg, #7e22ce, #9333ea);">
                                    <div class="row align-items-center text-center">
                                        <div class="col-4 border-end border-white border-opacity-25">
                                            <div class="text-uppercase" style="font-size: 10px; opacity: 0.85;">Total Price</div>
                                            <div class="fw-bold fs-6" id="dispTotalNet">Rs. 0.00</div>
                                        </div>
                                        <div class="col-4 border-end border-white border-opacity-25">
                                            <div class="text-uppercase" style="font-size: 10px; opacity: 0.85;">Advance Paid</div>
                                            <div class="fw-bold fs-6 text-warning" id="dispAdvance">Rs. 0.00</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-uppercase" style="font-size: 10px; opacity: 0.85;">Monthly Due</div>
                                            <div class="fw-bold fs-5 text-white" id="dispMonthly">Rs. 0.00 <span style="font-size: 11px;">/mo</span></div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-2 pt-2 border-top border-white border-opacity-25" style="font-size: 11px; opacity: 0.95;">
                                        Financed Balance: <strong id="dispFinanced">Rs. 0.00</strong> divided equally across <strong id="dispMonthsLabel">{{ $planDuration }} months</strong>
                                    </div>
                                </div>

                                {{-- Collapsible Schedule Preview --}}
                                <div class="card border rounded-3 overflow-hidden">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseInstallmentSchedule" aria-expanded="true">
                                        <span class="fw-bold text-dark" style="font-size: 12px;">
                                            <i class="fas fa-list-ol text-purple me-1" style="color: #9333EA;"></i> Generated Installment Schedule Preview
                                        </span>
                                        <i class="fas fa-chevron-down text-muted" style="font-size: 11px;"></i>
                                    </div>
                                    <div id="collapseInstallmentSchedule" class="collapse show">
                                        <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                                            <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
                                                <thead class="table-light sticky-top" style="font-size: 11px;">
                                                    <tr>
                                                        <th class="ps-3 py-1">Inst #</th>
                                                        <th class="py-1">Due Date</th>
                                                        <th class="text-end py-1">Amount</th>
                                                        <th class="text-center py-1 pe-3">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="installmentScheduleTbody">
                                                    {{-- Dynamically populated via recalculateInstallmentPlan() --}}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <input type="text" class="form-control form-control-sm" name="installment_notes" id="installmentNotes" placeholder="Optional notes for installment agreement..." value="{{ $sale->installmentPlan->notes ?? \'\' }}" style="font-size: 11.5px;">
                                </div>
                            </div>
                        </div>';

$chequeEndTargetNorm = str_replace("\r\n", "\n", $chequeEndTarget);
$installmentPanelHtmlNorm = str_replace("\r\n", "\n", $installmentPanelHtml);
if (strpos($es, $chequeEndTargetNorm) !== false) {
    $es = str_replace($chequeEndTargetNorm, $installmentPanelHtmlNorm, $es);
    echo "edit_sale.blade.php installment panel markup added\n";
} else {
    echo "edit_sale.blade.php chequeEndTarget NOT FOUND\n";
}

// Wire updateGrandTotals to recalculateInstallmentPlan in edit_sale
$ugtTarget = '                window.updateGrandTotals = function() {
                    __baseUGT();
                    if ($(\'#footerItemCount\').length) $(\'#footerItemCount\').text($(\'#itemsRowCount\').text());
                    if ($(\'#footerTotal\').length) $(\'#footerTotal\').text($(\'#tSub\').text());
                    if ($(\'#footerPaid\').length) $(\'#footerPaid\').text($(\'#receiptsTotal\').text());
                };';

$ugtReplace = '                window.updateGrandTotals = function() {
                    __baseUGT();
                    if ($(\'#footerItemCount\').length) $(\'#footerItemCount\').text($(\'#itemsRowCount\').text());
                    if ($(\'#footerTotal\').length) $(\'#footerTotal\').text($(\'#tSub\').text());
                    if ($(\'#footerPaid\').length) $(\'#footerPaid\').text($(\'#receiptsTotal\').text());
                    if (typeof recalculateInstallmentPlan === \'function\') {
                        recalculateInstallmentPlan();
                    }
                };';

$ugtTargetNorm = str_replace("\r\n", "\n", $ugtTarget);
$ugtReplaceNorm = str_replace("\r\n", "\n", $ugtReplace);
if (strpos($es, $ugtTargetNorm) !== false) {
    $es = str_replace($ugtTargetNorm, $ugtReplaceNorm, $es);
    echo "edit_sale.blade.php updateGrandTotals hooked\n";
} else {
    echo "edit_sale.blade.php ugtTarget NOT FOUND\n";
}

// Add installment scripts right before </script>
$scriptEndTarget = '            // Header Save Draft -> existing booking/save flow
            $(\'#btnHeaderSaveDraft\').on(\'click\', function() {
                $(\'#btnSave\').trigger(\'click\');
            });
        });
    </script>';

$installmentScript = '            // Header Save Draft -> existing booking/save flow
            $(\'#btnHeaderSaveDraft\').on(\'click\', function() {
                $(\'#btnSave\').trigger(\'click\');
            });

            // ==========================================
            // SALE INSTALLMENT PLAN (1 - 12 MONTHS)
            // ==========================================
            window.syncAdvanceToAccount = function() {
                if (!$(\'#toggleInstallmentPlan\').is(\':checked\')) return;
                var adv = parseFloat($(\'#installmentAdvanceAmount\').val()) || 0;
                var chosenAccId = $(\'#installmentAdvanceAccountId\').val();

                $(\'#rvWrapper .rv-row\').each(function() {
                    var accId = $(this).find(\'.rv-account\').val();
                    var $amt = $(this).find(\'.rv-amount\');
                    if (accId == chosenAccId) {
                        $amt.val(adv > 0 ? adv.toFixed(2) : \'\');
                    }
                });

                if (typeof calculateReceiptsTotal === \'function\') calculateReceiptsTotal();
                if (typeof updateBalancesOnPaymentChange === \'function\') updateBalancesOnPaymentChange();
            };

            window.recalculateInstallmentPlan = function() {
                if (!$(\'#toggleInstallmentPlan\').is(\':checked\')) {
                    return;
                }

                // 1. Get current invoice net total bill
                var totalBill = 0;
                if ($(\'#walkinNetTotal\').length && parseFloat($(\'#walkinNetTotal\').text()) > 0) {
                    totalBill = parseFloat($(\'#walkinNetTotal\').text());
                } else if ($(\'#totalBalance\').length && parseFloat($(\'#totalBalance\').val()) > 0) {
                    totalBill = parseFloat($(\'#totalBalance\').val());
                } else if ($(\'#tCurrentBill\').length && parseFloat($(\'#tCurrentBill\').text()) > 0) {
                    totalBill = parseFloat($(\'#tCurrentBill\').text());
                } else if ($(\'#tSub\').length && parseFloat($(\'#tSub\').text()) > 0) {
                    totalBill = parseFloat($(\'#tSub\').text());
                }

                var durationMonths = parseInt($(\'#installmentDurationMonths\').val()) || 12;
                if (durationMonths < 1) durationMonths = 1;
                if (durationMonths > 12) durationMonths = 12;

                var advance = parseFloat($(\'#installmentAdvanceAmount\').val()) || 0;
                if (advance < 0) advance = 0;
                if (advance > totalBill && totalBill > 0) {
                    advance = totalBill;
                    $(\'#installmentAdvanceAmount\').val(advance.toFixed(2));
                }

                var financed = Math.max(0, totalBill - advance);
                var monthlyInstallment = durationMonths > 0 ? (financed / durationMonths) : 0;

                // Update UI Card
                $(\'#dispTotalNet\').text(\'Rs. \' + totalBill.toLocaleString(\'en-US\', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $(\'#dispAdvance\').text(\'Rs. \' + advance.toLocaleString(\'en-US\', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $(\'#dispFinanced\').text(\'Rs. \' + financed.toLocaleString(\'en-US\', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $(\'#dispMonthly\').html(\'Rs. \' + monthlyInstallment.toLocaleString(\'en-US\', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + \' <span style="font-size: 11px;">/mo</span>\');
                $(\'#dispMonthsLabel\').text(durationMonths + \' months\');
                $(\'#badgeSelectedMonths\').text(durationMonths + \' Month\' + (durationMonths > 1 ? \'s\' : \'\') + \' Selected\');

                // Build Schedule Rows
                var tbody = $(\'#installmentScheduleTbody\');
                tbody.empty();

                var startDateStr = $(\'#installmentStartDate\').val();
                var baseDate = startDateStr ? new Date(startDateStr) : new Date();
                if (isNaN(baseDate.getTime())) {
                    baseDate = new Date();
                }

                var accumulated = 0;
                var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

                for (var i = 1; i <= durationMonths; i++) {
                    var dueD = new Date(baseDate.getFullYear(), baseDate.getMonth() + (i - 1), baseDate.getDate());
                    var dFormatted = dueD.getDate().toString().padStart(2, \'0\') + \'-\' + monthNames[dueD.getMonth()] + \'-\' + dueD.getFullYear();
                    
                    var instAmount = (i === durationMonths) ? (financed - accumulated) : monthlyInstallment;
                    instAmount = Math.max(0, instAmount);
                    accumulated += Math.round(monthlyInstallment * 100) / 100;

                    var rowHtml = \'<tr>\' +
                        \'<td class="ps-3 fw-bold text-purple" style="color: #9333EA;">#\' + i + \'</td>\' +
                        \'<td><i class="far fa-calendar-alt text-muted me-1"></i> \' + dFormatted + \'</td>\' +
                        \'<td class="text-end fw-bold">Rs. \' + instAmount.toLocaleString(\'en-US\', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + \'</td>\' +
                        \'<td class="text-center pe-3"><span class="badge bg-light text-secondary border" style="font-size: 10px;">Scheduled</span></td>\' +
                        \'</tr>\';
                    tbody.append(rowHtml);
                }
            };

            // Toggle switch handler
            $(\'#toggleInstallmentPlan\').on(\'change\', function() {
                if ($(this).is(\':checked\')) {
                    $(\'#installmentPlanDetails\').stop(true, true).slideDown(250);
                    syncAdvanceToAccount();
                    window.recalculateInstallmentPlan();
                } else {
                    $(\'#installmentPlanDetails\').stop(true, true).slideUp(250);
                }
            });

            // Month pills click handler
            $(\'#installmentMonthsPills\').on(\'click\', \'.btn-month-pill\', function(e) {
                e.preventDefault();
                $(\'#installmentMonthsPills .btn-month-pill\').removeClass(\'active\');
                $(this).addClass(\'active\');
                var m = $(this).data(\'months\');
                $(\'#installmentDurationMonths\').val(m);
                window.recalculateInstallmentPlan();
            });

            // Advance amount change
            $(\'#installmentAdvanceAmount\').on(\'input change\', function() {
                syncAdvanceToAccount();
                window.recalculateInstallmentPlan();
            });

            // Quick advance percentage buttons
            $(\'#btnAdvZero\').on(\'click\', function() {
                $(\'#installmentAdvanceAmount\').val(0);
                syncAdvanceToAccount();
                window.recalculateInstallmentPlan();
            });
            $(\'#btnAdv25\').on(\'click\', function() {
                var total = parseFloat($(\'#tCurrentBill\').text()) || parseFloat($(\'#totalBalance\').val()) || 0;
                var adv = Math.round(total * 0.25);
                $(\'#installmentAdvanceAmount\').val(adv);
                syncAdvanceToAccount();
                window.recalculateInstallmentPlan();
            });
            $(\'#btnAdv50\').on(\'click\', function() {
                var total = parseFloat($(\'#tCurrentBill\').text()) || parseFloat($(\'#totalBalance\').val()) || 0;
                var adv = Math.round(total * 0.50);
                $(\'#installmentAdvanceAmount\').val(adv);
                syncAdvanceToAccount();
                window.recalculateInstallmentPlan();
            });

            // Advance account change
            $(\'#installmentAdvanceAccountId\').on(\'change\', function() {
                syncAdvanceToAccount();
            });

            // Due date picker change
            $(\'#installmentStartDate\').on(\'change\', function() {
                window.recalculateInstallmentPlan();
            });

            // If already checked on edit load
            if ($(\'#toggleInstallmentPlan\').is(\':checked\')) {
                $(\'#installmentPlanDetails\').show();
                setTimeout(function() {
                    window.recalculateInstallmentPlan();
                }, 300);
            }
        });
    </script>';

$scriptEndTargetNorm = str_replace("\r\n", "\n", $scriptEndTarget);
$installmentScriptNorm = str_replace("\r\n", "\n", $installmentScript);
if (strpos($es, $scriptEndTargetNorm) !== false) {
    $es = str_replace($scriptEndTargetNorm, $installmentScriptNorm, $es);
    echo "edit_sale.blade.php installment scripts added\n";
} else {
    echo "edit_sale.blade.php scriptEndTarget NOT FOUND\n";
}

file_put_contents($esPath, $es);
echo "ALL UPDATES COMPLETE.\n";
