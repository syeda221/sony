<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductSerial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryCostService
{
    /**
     * Compute perpetual moving-average inventory state (remaining qty, remaining value,
     * current unit cost, and per-sale-item COGS map) for a product (optionally filtered to a variant).
     *
     * Because costs are deducted chronologically as sales occur, future purchases
     * never retroactively alter past sales' COGS, and (COGS + remaining stock value)
     * stays aligned with total purchases.
     */
    public function computePerpetualState(
        int $productId,
        ?string $upToDate = null,
        ?int $excludeSaleId = null,
        ?array $variant = null
    ): array {
        $product = Product::find($productId);
        if (!$product) {
            return [
                'qty' => 0.0,
                'value' => 0.0,
                'unit_cost' => 0.0,
                'sold_cogs_map' => [],
            ];
        }

        $defaultCost = $this->getDefaultProductCost($product, $variant);
        $dateLimit = $upToDate ? substr(trim($upToDate), 0, 10) : null;

        $events = [];

        // 1. Opening / Initial stock movements (if recorded in stock_movements)
        $initMovements = DB::table('stock_movements')
            ->where('product_id', $productId)
            ->whereIn('ref_type', ['INIT', 'OPENING_STOCK'])
            ->when($dateLimit, fn ($q) => $q->whereDate('created_at', '<=', $dateLimit))
            ->get();

        foreach ($initMovements as $im) {
            $qty = (float) $im->qty;
            if ($qty <= 0) {
                continue;
            }
            $events[] = [
                'ts' => $im->created_at ?? '1970-01-01 00:00:00',
                'order' => 0,
                'type' => 'in',
                'qty' => $qty,
                'amount' => $qty * $defaultCost,
            ];
        }

        // 2. Approved / Posted Purchases
        $purchQuery = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->where('pi.product_id', $productId)
            ->whereIn('p.status_purchase', ['approved', 'posted', 'Returned', 'Partial']);

        if ($dateLimit) {
            $purchQuery->whereRaw('COALESCE(p.purchase_date, DATE(p.created_at)) <= ?', [$dateLimit]);
        }

        $purchItems = $purchQuery->select(
            'pi.qty',
            'pi.unit',
            'pi.pieces_per_box',
            'pi.line_total',
            'pi.color',
            'p.subtotal as p_subtotal',
            'p.net_amount as p_net_amount',
            'p.purchase_date',
            'p.created_at as p_created_at'
        )->get();

        foreach ($purchItems as $pItem) {
            if ($variant !== null && !$this->matchVariant($pItem->color ?? null, $variant)) {
                continue;
            }
            $pieces = $this->purchaseItemToPieces($pItem);
            if ($pieces <= 0) {
                continue;
            }
            // Preserve line_total as product cost (order discount is tracked separately as vendor-level income)
            $netAmount = (float) $pItem->line_total;

            $pDate = $pItem->purchase_date ?: substr((string) $pItem->p_created_at, 0, 10);
            $pTime = $pItem->p_created_at ? substr((string) $pItem->p_created_at, 11, 8) : '00:00:00';
            $events[] = [
                'ts' => $pDate . ' ' . $pTime,
                'order' => 1,
                'type' => 'in',
                'qty' => $pieces,
                'amount' => $netAmount,
            ];
        }

        // 3. Posted / Returned Sales
        $hasSerialCol = Schema::hasColumn('sale_items', 'serials');
        $hasBatchCol = Schema::hasColumn('sale_items', 'batch_id');
        $hasSalePurchCol = Schema::hasColumn('sale_items', 'purchase_price');

        $saleQuery = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->where('si.product_id', $productId)
            ->whereIn('s.sale_status', ['posted', 'returned']);

        if ($excludeSaleId) {
            $saleQuery->where('s.id', '!=', $excludeSaleId);
        }
        if ($dateLimit) {
            $saleQuery->whereDate('s.created_at', '<=', $dateLimit);
        }

        $saleCols = [
            'si.id as sale_item_id',
            'si.total_pieces',
            'si.color',
            's.created_at as s_created_at',
            's.updated_at as s_updated_at',
            $hasSerialCol ? 'si.serials' : DB::raw('NULL as serials'),
            $hasBatchCol ? 'si.batch_id' : DB::raw('NULL as batch_id'),
            $hasSalePurchCol ? 'si.purchase_price' : DB::raw('NULL as purchase_price'),
        ];

        $saleItems = $saleQuery->select($saleCols)->get();

        foreach ($saleItems as $sItem) {
            if ($variant !== null && !$this->matchVariant($sItem->color ?? null, $variant)) {
                continue;
            }
            $pieces = (float) ($sItem->total_pieces ?? 0);
            if ($pieces <= 0) {
                continue;
            }

            $sDate = substr((string) $sItem->s_created_at, 0, 10);
            $sTime = substr((string) $sItem->s_created_at, 11, 8);
            if ($sTime === '00:00:00' && !empty($sItem->s_updated_at)) {
                $sTime = substr((string) $sItem->s_updated_at, 11, 8);
            }

            $events[] = [
                'ts' => $sDate . ' ' . ($sTime ?: '23:59:59'),
                'order' => 2,
                'type' => 'sale',
                'sale_item_id' => (int) $sItem->sale_item_id,
                'qty' => $pieces,
                'serials' => $sItem->serials ?? null,
                'batch_id' => $sItem->batch_id ?? null,
                'purchase_price' => $sItem->purchase_price ?? null,
            ];
        }

        // 4. Sale Returns
        $hasRetPurchCol = Schema::hasColumn('sale_return_items', 'purchase_price');
        $retQuery = DB::table('sale_return_items as sri')
            ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
            ->where('sri.product_id', $productId);

        if ($dateLimit) {
            $retQuery->whereDate('sr.created_at', '<=', $dateLimit);
        }

        $retItems = $retQuery->select(
            'sri.qty',
            'sri.color',
            'sr.created_at as sr_created_at',
            $hasRetPurchCol ? 'sri.purchase_price' : DB::raw('NULL as purchase_price')
        )->get();

        foreach ($retItems as $rItem) {
            if ($variant !== null && !empty($rItem->color) && !$this->matchVariant($rItem->color, $variant)) {
                continue;
            }
            $pieces = (float) ($rItem->qty ?? 0);
            if ($pieces <= 0) {
                continue;
            }
            $events[] = [
                'ts' => (string) ($rItem->sr_created_at ?? '1970-01-01 00:00:00'),
                'order' => 3,
                'type' => 'sale_return',
                'qty' => $pieces,
                'purchase_price' => $rItem->purchase_price ?? null,
            ];
        }

        // 5. Purchase Returns
        $pRetQuery = DB::table('purchase_return_items as pri')
            ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
            ->where('pri.product_id', $productId);

        if ($dateLimit) {
            $pRetQuery->whereDate('pr.created_at', '<=', $dateLimit);
        }

        $pRetItems = $pRetQuery->select('pri.qty', 'pri.line_total', 'pri.color', 'pr.created_at as pr_created_at')->get();

        foreach ($pRetItems as $prItem) {
            if ($variant !== null && !$this->matchVariant($prItem->color ?? null, $variant)) {
                continue;
            }
            $pieces = (float) ($prItem->qty ?? 0);
            if ($pieces <= 0) {
                continue;
            }
            $events[] = [
                'ts' => (string) ($prItem->pr_created_at ?? '1970-01-01 00:00:00'),
                'order' => 4,
                'type' => 'purchase_return',
                'qty' => $pieces,
                'amount' => (float) ($prItem->line_total ?? 0),
            ];
        }

        usort($events, function ($a, $b) {
            $cmp = strcmp($a['ts'], $b['ts']);
            return $cmp !== 0 ? $cmp : ($a['order'] <=> $b['order']);
        });

        $hasSerialsTable = Schema::hasTable('product_serials');
        $hasBatchesTable = Schema::hasTable('product_batches');

        $runningQty = 0.0;
        $runningValue = 0.0;
        $soldCogsMap = [];

        foreach ($events as $ev) {
            if ($ev['type'] === 'in') {
                $runningQty += $ev['qty'];
                $runningValue += $ev['amount'];
            } elseif ($ev['type'] === 'sale') {
                $pieces = $ev['qty'];
                $cost = null;

                $itSerials = !empty($ev['serials'])
                    ? (is_array($ev['serials']) ? $ev['serials'] : json_decode($ev['serials'], true))
                    : [];

                if (!empty($itSerials) && is_array($itSerials) && $hasSerialsTable) {
                    $serialCosts = ProductSerial::where('product_id', $productId)
                        ->whereIn('serial_number', $itSerials)
                        ->pluck('cost_price');
                    if ($serialCosts->count() > 0) {
                        $avgNow = $runningQty > 0 ? ($runningValue / $runningQty) : $defaultCost;
                        $missing = max(0, count($itSerials) - $serialCosts->count());
                        $cost = (float) $serialCosts->sum() + ($missing * $avgNow);
                    }
                } elseif (!empty($ev['batch_id']) && $hasBatchesTable) {
                    $batchCost = (float) (ProductBatch::where('id', $ev['batch_id'])->value('cost_price') ?? 0);
                    if ($batchCost > 0) {
                        $cost = $pieces * $batchCost;
                    }
                } elseif (!empty($ev['purchase_price']) && (float) $ev['purchase_price'] > 0) {
                    $cost = $pieces * (float) $ev['purchase_price'];
                }

                if ($cost === null) {
                    $avgNow = $runningQty > 0 ? ($runningValue / $runningQty) : $defaultCost;
                    $cost = $pieces * $avgNow;
                }

                $soldCogsMap[$ev['sale_item_id']] = $cost;
                $runningQty = max(0.0, $runningQty - $pieces);
                $runningValue = $runningQty > 0 ? max(0.0, $runningValue - $cost) : 0.0;
            } elseif ($ev['type'] === 'sale_return') {
                $rUnit = (!empty($ev['purchase_price']) && (float) $ev['purchase_price'] > 0)
                    ? (float) $ev['purchase_price']
                    : ($runningQty > 0 ? ($runningValue / $runningQty) : $defaultCost);
                $runningQty += $ev['qty'];
                $runningValue += ($ev['qty'] * $rUnit);
            } elseif ($ev['type'] === 'purchase_return') {
                $runningQty = max(0.0, $runningQty - $ev['qty']);
                $runningValue = $runningQty > 0 ? max(0.0, $runningValue - $ev['amount']) : 0.0;
            }
        }

        $unitCost = $runningQty > 0 ? ($runningValue / $runningQty) : $defaultCost;

        return [
            'qty' => $runningQty,
            'value' => $runningValue,
            'unit_cost' => $unitCost,
            'sold_cogs_map' => $soldCogsMap,
        ];
    }

    /**
     * Get the current unit purchase cost for a product/variant at the moment of sale.
     */
    public function getCurrentUnitCostForSale(
        int $productId,
        ?string $saleDate = null,
        ?int $excludeSaleId = null,
        ?array $variant = null
    ): float {
        $state = $this->computePerpetualState($productId, $saleDate, $excludeSaleId, $variant);
        if ($state['qty'] <= 0 && $variant !== null) {
            // Fallback to product-wide state if variant-specific purchases weren't matched
            $state = $this->computePerpetualState($productId, $saleDate, $excludeSaleId, null);
        }

        return round((float) $state['unit_cost'], 4);
    }

    private function getDefaultProductCost(Product $product, ?array $variant = null): float
    {
        if ($variant !== null && !empty($variant['purch_price']) && (float) $variant['purch_price'] > 0) {
            return (float) $variant['purch_price'];
        }
        if ($product->size_mode === 'by_size') {
            return (float) ($product->pieces_per_m2 ?? 0) * (float) ($product->purchase_price_per_m2 ?? 0);
        }

        return (float) ($product->purchase_price_per_piece ?? 0);
    }

    private function purchaseItemToPieces(object $pItem): float
    {
        $u = strtolower(trim((string) ($pItem->unit ?? '')));
        $ppb = (float) ($pItem->pieces_per_box ?? 1);
        if ($ppb <= 0) {
            $ppb = 1;
        }
        if (in_array($u, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl'])) {
            return (float) $pItem->qty * $ppb;
        }
        if (in_array($u, ['gm', 'g'])) {
            return (float) $pItem->qty / 1000.0;
        }

        return (float) $pItem->qty;
    }

    private function matchVariant(?string $rawColor, array $variant): bool
    {
        if (empty($rawColor)) {
            return false;
        }
        $decoded = null;
        $b64 = base64_decode($rawColor, true);
        if ($b64 !== false) {
            $decoded = json_decode($b64, true);
        }
        if (!is_array($decoded)) {
            $decoded = json_decode($rawColor, true);
        }
        if (!is_array($decoded)) {
            return false;
        }

        $vColor = strtolower(trim((string) ($variant['color'] ?? '-')));
        $vSize = strtolower(trim((string) ($variant['size'] ?? '-')));
        if ($vColor === '') {
            $vColor = '-';
        }
        if ($vSize === '') {
            $vSize = '-';
        }

        $iColor = strtolower(trim((string) ($decoded['color'] ?? '-')));
        $iSize = strtolower(trim((string) ($decoded['size'] ?? '-')));
        if ($iColor === '') {
            $iColor = '-';
        }
        if ($iSize === '') {
            $iSize = '-';
        }

        return $iColor === $vColor && $iSize === $vSize;
    }
}
