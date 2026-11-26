<?php

namespace App\Console\Commands;

use App\Services\WarehouseStockSyncService;
use Illuminate\Console\Command;

class SyncWarehouseStockData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:sync-warehouses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile warehouse_stocks (total_pieces & boxes quantity) with serials and batches';

    /**
     * Execute the console command.
     */
    public function handle(WarehouseStockSyncService $syncService): int
    {
        $this->info('Synchronizing warehouse stocks...');
        $stats = $syncService->syncAll();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Boxes converted to pieces (total_pieces == 0)', $stats['fixed_box_to_pieces']],
                ['Warehouse stock rows synced from serials', $stats['synced_serial_rows']],
                ['Serial warehouse assignments realigned', $stats['reassigned_serials']],
                ['Warehouse stock rows synced from batches', $stats['synced_batch_rows']],
                ['Draft purchase phantom serials cleaned', $stats['cleaned_draft_serials']],
            ]
        );

        $this->info('Warehouse stock synchronization completed.');

        return self::SUCCESS;
    }
}
