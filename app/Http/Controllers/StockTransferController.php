<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductSerial;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index()
    {
        $transfers = StockTransfer::with('fromWarehouse', 'toWarehouse', 'product')->latest()->get();

        return view('admin_panel.warehouses.stock_transfers.index', compact('transfers'));
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $products = Product::orderBy('item_name')->get();

        return view('admin_panel.warehouses.stock_transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|required_without:to_shop|integer|exists:warehouses,id',
            'to_shop' => 'nullable|boolean',
            'product_id' => 'required|array',
            'product_id.*' => 'required|integer|exists:products,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric|min:1',
            'remarks' => 'nullable|string|max:500',
        ]);

        $fromWarehouse = (int) $request->from_warehouse_id;
        $toWarehouse = $request->to_warehouse_id ? (int) $request->to_warehouse_id : null;
        $toShop = $request->boolean('to_shop');
        $remarks = $request->remarks;

        if (! $toShop && ! $toWarehouse) {
            return back()->with('error', 'Please select a destination warehouse or choose Transfer to Shop.')->withInput();
        }

        if (! $toShop && $fromWarehouse === $toWarehouse) {
            return back()->with('error', 'Source and Destination warehouses cannot be the same.')->withInput();
        }

        // Aggregate quantities by product
        $groupped = [];
        foreach ($request->product_id as $index => $pid) {
            $qty = (float) ($request->quantity[$index] ?? 0);
            if ($qty > 0) {
                if (! isset($groupped[$pid])) {
                    $groupped[$pid] = 0;
                }
                $groupped[$pid] += $qty;
            }
        }

        if (empty($groupped)) {
            return back()->with('error', 'Please select at least one product with quantity greater than 0.')->withInput();
        }

        try {
            DB::transaction(function () use ($fromWarehouse, $toWarehouse, $toShop, $remarks, $groupped) {
                foreach ($groupped as $productId => $totalQty) {
                    $product = Product::findOrFail($productId);
                    $ppb = $product->pieces_per_box > 0 ? (float) $product->pieces_per_box : 1.0;

                    // Check source stock for available pieces
                    $sourceStock = WarehouseStock::firstOrCreate(
                        [
                            'warehouse_id' => $fromWarehouse,
                            'product_id' => $productId,
                        ],
                        [
                            'quantity' => 0,
                            'total_pieces' => 0,
                        ]
                    );

                    $sourceStock = WarehouseStock::where('id', $sourceStock->id)->lockForUpdate()->first();

                    $available = (float) $sourceStock->total_pieces;
                    if ($available <= 0 && (float) $sourceStock->quantity > 0) {
                        $available = (float) $sourceStock->quantity * $ppb;
                    }

                    if ($product->tracking_type === 'serial') {
                        $sCount = (float) ProductSerial::where('product_id', $productId)
                            ->where('warehouse_id', $fromWarehouse)
                            ->where('status', 'available')
                            ->count();
                        $available = max($available, $sCount);
                    } elseif ($product->tracking_type === 'batch') {
                        $bSum = (float) ProductBatch::where('product_id', $productId)
                            ->where('warehouse_id', $fromWarehouse)
                            ->where('qty', '>', 0)
                            ->sum('qty');
                        $available = max($available, $bSum);
                    }

                    if ($available < $totalQty) {
                        $availFormatted = number_format($available, 2);
                        throw new \Exception("Insufficient stock for {$product->item_name}. Requested: {$totalQty}, Available: {$availFormatted}");
                    }

                    // Reduce stock from source warehouse
                    $newSourcePieces = max(0.0, $available - $totalQty);
                    $sourceStock->total_pieces = $newSourcePieces;
                    $sourceStock->quantity = (int) floor($newSourcePieces / $ppb);
                    $sourceStock->save();

                    // Setup and add stock to destination warehouse if not transferring to shop
                    if (! $toShop && $toWarehouse) {
                        $destStock = WarehouseStock::firstOrCreate(
                            [
                                'warehouse_id' => $toWarehouse,
                                'product_id' => $productId,
                            ],
                            [
                                'quantity' => 0,
                                'total_pieces' => 0,
                            ]
                        );

                        $destStock = WarehouseStock::where('id', $destStock->id)->lockForUpdate()->first();

                        $destPieces = (float) ($destStock->total_pieces ?? 0.0);
                        if ($destPieces <= 0 && (float) ($destStock->quantity ?? 0.0) > 0) {
                            $destPieces = (float) $destStock->quantity * $ppb;
                        }

                        $newDestPieces = $destPieces + $totalQty;
                        $destStock->total_pieces = $newDestPieces;
                        $destStock->quantity = (int) floor($newDestPieces / $ppb);
                        $destStock->save();

                        // Move available serials (if any) from source warehouse to destination warehouse
                        if ($product->tracking_type === 'serial') {
                            $serialMoveCount = (int) round($totalQty);
                            if ($serialMoveCount > 0) {
                                $serialIds = ProductSerial::where('product_id', $productId)
                                    ->where('warehouse_id', $fromWarehouse)
                                    ->where('status', 'available')
                                    ->orderBy('id', 'asc')
                                    ->limit($serialMoveCount)
                                    ->pluck('id');

                                if ($serialIds->count() < $serialMoveCount) {
                                    $fallbackIds = ProductSerial::where('product_id', $productId)
                                        ->where('status', 'available')
                                        ->whereNotIn('id', $serialIds)
                                        ->orderBy('id', 'asc')
                                        ->limit($serialMoveCount - $serialIds->count())
                                        ->pluck('id');
                                    $serialIds = $serialIds->merge($fallbackIds);
                                }

                                if ($serialIds->isNotEmpty()) {
                                    ProductSerial::whereIn('id', $serialIds)->update(['warehouse_id' => $toWarehouse]);
                                }

                                // Align WarehouseStock pieces directly with available serial count for both warehouses
                                $sourceCount = (float) ProductSerial::where('product_id', $productId)->where('warehouse_id', $fromWarehouse)->where('status', 'available')->count();
                                $sourceStock->total_pieces = $sourceCount;
                                $sourceStock->quantity = (int) floor($sourceCount / $ppb);
                                $sourceStock->save();

                                $destCount = (float) ProductSerial::where('product_id', $productId)->where('warehouse_id', $toWarehouse)->where('status', 'available')->count();
                                $destStock->total_pieces = $destCount;
                                $destStock->quantity = (int) floor($destCount / $ppb);
                                $destStock->save();
                            }
                        }

                        // Move batch quantities (if any) FIFO from source warehouse to destination warehouse
                        if ($product->tracking_type === 'batch') {
                            $remainingBatchQty = (float) $totalQty;
                            $sourceBatches = ProductBatch::where('product_id', $productId)
                                ->where('warehouse_id', $fromWarehouse)
                                ->where('qty', '>', 0)
                                ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC, id ASC')
                                ->lockForUpdate()
                                ->get();

                            foreach ($sourceBatches as $sBatch) {
                                if ($remainingBatchQty <= 0) {
                                    break;
                                }
                                $take = min((float) $sBatch->qty, $remainingBatchQty);
                                $sBatch->qty = max(0, (float) $sBatch->qty - $take);
                                $sBatch->save();

                                $destBatch = ProductBatch::firstOrCreate(
                                    [
                                        'product_id' => $productId,
                                        'warehouse_id' => $toWarehouse,
                                        'batch_no' => $sBatch->batch_no,
                                    ],
                                    [
                                        'variant_key' => $sBatch->variant_key,
                                        'mfg_date' => $sBatch->mfg_date,
                                        'expiry_date' => $sBatch->expiry_date,
                                        'qty' => 0,
                                        'cost_price' => $sBatch->cost_price,
                                        'remarks' => $sBatch->remarks,
                                    ]
                                );
                                $destBatch->qty = (float) $destBatch->qty + $take;
                                $destBatch->save();

                                $remainingBatchQty -= $take;
                            }

                            // Align WarehouseStock with active batch sums
                            $sBatchSum = (float) ProductBatch::where('product_id', $productId)->where('warehouse_id', $fromWarehouse)->where('qty', '>', 0)->sum('qty');
                            $sourceStock->total_pieces = $sBatchSum;
                            $sourceStock->quantity = (int) floor($sBatchSum / $ppb);
                            $sourceStock->save();

                            $dBatchSum = (float) ProductBatch::where('product_id', $productId)->where('warehouse_id', $toWarehouse)->where('qty', '>', 0)->sum('qty');
                            $destStock->total_pieces = $dBatchSum;
                            $destStock->quantity = (int) floor($dBatchSum / $ppb);
                            $destStock->save();
                        }
                    }

                    // Record Transfer
                    $transfer = StockTransfer::create([
                        'from_warehouse_id' => $fromWarehouse,
                        'to_warehouse_id' => $toShop ? null : $toWarehouse,
                        'to_shop' => $toShop,
                        'product_id' => $productId,
                        'quantity' => (int) round($totalQty),
                        'remarks' => $remarks,
                    ]);

                    $fromWhObj = Warehouse::find($fromWarehouse);
                    $toWhObj = $toWarehouse ? Warehouse::find($toWarehouse) : null;
                    $fromName = $fromWhObj ? $fromWhObj->warehouse_name : "Warehouse #{$fromWarehouse}";
                    $destName = $toShop ? 'Shop' : ($toWhObj ? $toWhObj->warehouse_name : "Warehouse #{$toWarehouse}");

                    // Log Movement (OUT)
                    DB::table('stock_movements')->insert([
                        'product_id' => $productId,
                        'type' => 'out',
                        'qty' => -1 * abs($totalQty),
                        'ref_type' => 'TRANSFER_OUT',
                        'ref_id' => $transfer->id,
                        'note' => "Transfer from {$fromName} to {$destName}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Log Movement (IN)
                    if (! $toShop && $toWarehouse) {
                        DB::table('stock_movements')->insert([
                            'product_id' => $productId,
                            'type' => 'in',
                            'qty' => abs($totalQty),
                            'ref_type' => 'TRANSFER_IN',
                            'ref_id' => $transfer->id,
                            'note' => "Transfer to {$destName} from {$fromName}",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        } catch (\Exception $e) {
            return back()->with('error', 'Transfer Failed: '.$e->getMessage())->withInput();
        }

        return redirect()->route('stock_transfers.index')->with('success', 'Stock transferred successfully.');
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        return back()->with('error', 'Deleting transfers not allowed.');
    }

    public function getStockQuantity(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $productId = $request->product_id;

        if (! $warehouseId || ! $productId) {
            return response()->json(['quantity' => 0, 'total_pieces' => 0]);
        }

        $product = Product::find($productId);
        $ppb = ($product && $product->pieces_per_box > 0) ? (float) $product->pieces_per_box : 1.0;

        $stock = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();

        $avail = $stock ? (float) $stock->total_pieces : 0.0;
        if ($avail <= 0 && $stock && (float) $stock->quantity > 0) {
            $avail = (float) $stock->quantity * $ppb;
        }

        if ($product && $product->tracking_type === 'serial') {
            $sCnt = (float) ProductSerial::where('product_id', $productId)->where('warehouse_id', $warehouseId)->where('status', 'available')->count();
            $avail = max($avail, $sCnt);
        } elseif ($product && $product->tracking_type === 'batch') {
            $bSum = (float) ProductBatch::where('product_id', $productId)->where('warehouse_id', $warehouseId)->where('qty', '>', 0)->sum('qty');
            $avail = max($avail, $bSum);
        }

        return response()->json([
            'quantity' => $avail,
            'total_pieces' => $avail,
        ]);
    }

    public function getProductsByWarehouse(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        if (! $warehouseId) {
            return response()->json([]);
        }

        $stocks = WarehouseStock::with('product')
            ->where('warehouse_id', $warehouseId)
            ->get();

        $serialProductIds = ProductSerial::where('warehouse_id', $warehouseId)
            ->where('status', 'available')
            ->distinct()
            ->pluck('product_id')
            ->toArray();

        $batchProductIds = ProductBatch::where('warehouse_id', $warehouseId)
            ->where('qty', '>', 0)
            ->distinct()
            ->pluck('product_id')
            ->toArray();

        $allProductIds = array_unique(array_merge(
            $stocks->pluck('product_id')->toArray(),
            $serialProductIds,
            $batchProductIds
        ));

        $products = [];
        foreach ($allProductIds as $pid) {
            $product = Product::find($pid);
            if (! $product) continue;

            $stock = $stocks->firstWhere('product_id', $pid);
            $ppb = $product->pieces_per_box > 0 ? (float) $product->pieces_per_box : 1.0;
            $avail = $stock ? (float) $stock->total_pieces : 0.0;
            if ($avail <= 0 && $stock && (float) $stock->quantity > 0) {
                $avail = (float) $stock->quantity * $ppb;
            }

            if ($product->tracking_type === 'serial') {
                $sCnt = (float) ProductSerial::where('product_id', $pid)->where('warehouse_id', $warehouseId)->where('status', 'available')->count();
                $avail = max($avail, $sCnt);
            } elseif ($product->tracking_type === 'batch') {
                $bSum = (float) ProductBatch::where('product_id', $pid)->where('warehouse_id', $warehouseId)->where('qty', '>', 0)->sum('qty');
                $avail = max($avail, $bSum);
            }

            if ($avail > 0) {
                $code = $product->item_code ? " ({$product->item_code})" : '';
                $products[] = [
                    'id' => $product->id,
                    'item_name' => $product->item_name . $code,
                    'available_stock' => $avail,
                ];
            }
        }

        usort($products, fn($a, $b) => strcmp($a['item_name'], $b['item_name']));

        return response()->json($products);
    }
}

// delvivery challan
// convet out per  stock ledger maintain
