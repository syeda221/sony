<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseStock;

class PurchaseWarehouseRoutingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_updates_stock_per_row_warehouse()
    {
        // Create two warehouses
        $warehouseA = Warehouse::factory()->create(['name' => 'Warehouse A']);
        $warehouseB = Warehouse::factory()->create(['name' => 'Warehouse B']);

        // Create a product
        $product = Product::factory()->create(['name' => 'Test Product', 'pieces_per_box' => 10]);

        // Payload with two items, each assigned a different warehouse
        $payload = [
            'warehouse_id' => $warehouseA->id, // default for order
            'supplier_id'  => 1, // adjust if needed
            'items' => [
                [
                    'product_id'   => $product->id,
                    'quantity'     => 5,
                    'unit_cost'    => 100,
                    'warehouse_id' => $warehouseA->id,
                ],
                [
                    'product_id'   => $product->id,
                    'quantity'     => 3,
                    'unit_cost'    => 100,
                    'warehouse_id' => $workspaceB->id,
                ],
            ],
        ];

        $response = $this->postJson(route('purchase.store'), $payload);
        $response->assertStatus(201);

        $stockA = WarehouseStock::where('warehouse_id', $warehouseA->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($stockA);
        $this->assertEquals(5, $stockA->total_pieces);

        $stockB = WarehouseStock::where('warehouse_id', $warehouseB->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($stockB);
        $this->assertEquals(3, $stockB->total_pieces);
    }
}

?>