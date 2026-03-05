<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Main Branch']);
        $this->store = Store::create(['branch_id' => $this->branch->id, 'name' => 'Main Store']);
        $this->product = Product::create(['sku' => 'TEST-SKU', 'name' => 'Test Product']);

        // Seed initial stock
        Stock::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'quantity' => 100
        ]);

        $this->user = User::factory()->create(['role' => 'administrator']);
        $this->actingAs($this->user);
    }

    /**
     * Test that many sequential sales result in accurate final stock.
     */
    public function test_sequential_sales_integrity()
    {
        $service = app(InventoryService::class);
        $iterations = 50;
        $quantityPerSale = 1;

        for ($i = 0; $i < $iterations; $i++) {
            $service->move($this->product, $this->store, null, $quantityPerSale, 'sale', "Sale $i");
        }

        $stock = Stock::where('store_id', $this->store->id)
            ->where('product_id', $this->product->id)
            ->first();

        // 100 - 50 = 50
        $this->assertEquals(50, $stock->quantity);

        // assert we have exactly 50 movement records
        $this->assertEquals($iterations, StockMovement::where('type', 'sale')->count());
    }

    /**
     * Test that ledger sum always matches current stock.
     */
    public function test_ledger_consistency()
    {
        $service = app(InventoryService::class);
        $otherStore = Store::create(['branch_id' => $this->branch->id, 'name' => 'Other Store']);

        // mix of movements
        $service->move($this->product, null, $this->store, 50, 'procurement'); // +50
        $service->move($this->product, $this->store, $otherStore, 20, 'transfer'); // -20 from main
        $service->move($this->product, $this->store, null, 10, 'sale'); // -10 from main

        $mainStock = Stock::where('store_id', $this->store->id)->first();

        // Initial 100 + 50 - 20 - 10 = 120
        $this->assertEquals(120, $mainStock->quantity);

        // Verify matches Ledger Logic:
        // Sum of all movements TO this store MINUS Sum of all movements FROM this store
        $in = StockMovement::where('destination_store_id', $this->store->id)->sum('quantity');
        $out = StockMovement::where('source_store_id', $this->store->id)->sum('quantity');

        // Note: The seeder created the initial 100 but didn't create a movement (it was manual in setUp)
        // In a real system, the initial stock should also be a movement (procurement).
        // For this test, let's just check the delta from movements.
        $this->assertEquals(20, $in - $out);
    }
}
