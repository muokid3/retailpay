<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\Branch;
use App\Models\User;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Exceptions\InsufficientStockException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
    }

    public function test_procurement_increments_stock_and_records_movement()
    {
        $branch = Branch::create(['name' => 'Test Branch']);
        $store = Store::create(['branch_id' => $branch->id, 'name' => 'Test Store']);
        $product = Product::create(['sku' => 'TEST-01', 'name' => 'Test Product']);

        $this->service->recordProcurement($product, $store, 100, 'PO-123');

        $this->assertDatabaseHas('stocks', [
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'destination_store_id' => $store->id,
            'quantity' => 100,
            'type' => 'procurement',
            'reference' => 'PO-123',
        ]);
    }

    public function test_transfer_moves_stock_between_stores()
    {
        $branch = Branch::create(['name' => 'Test Branch']);
        $storeA = Store::create(['branch_id' => $branch->id, 'name' => 'Store A']);
        $storeB = Store::create(['branch_id' => $branch->id, 'name' => 'Store B']);
        $product = Product::create(['sku' => 'TEST-01', 'name' => 'Test Product']);

        // Seed initial stock in A
        Stock::create(['store_id' => $storeA->id, 'product_id' => $product->id, 'quantity' => 50]);

        $this->service->transferStock($product, $storeA, $storeB, 20, 'REF-456');

        $this->assertEquals(30, Stock::where('store_id', $storeA->id)->where('product_id', $product->id)->first()->quantity);
        $this->assertEquals(20, Stock::where('store_id', $storeB->id)->where('product_id', $product->id)->first()->quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'source_store_id' => $storeA->id,
            'destination_store_id' => $storeB->id,
            'quantity' => 20,
            'type' => 'transfer',
        ]);
    }

    public function test_sale_deducts_stock()
    {
        $branch = Branch::create(['name' => 'Test Branch']);
        $store = Store::create(['branch_id' => $branch->id, 'name' => 'Test Store']);
        $product = Product::create(['sku' => 'TEST-01', 'name' => 'Test Product']);

        // Seed initial stock
        Stock::create(['store_id' => $store->id, 'product_id' => $product->id, 'quantity' => 10]);

        $this->service->recordSale($product, $store, 3, 'SALE-789');

        $this->assertEquals(7, Stock::where('store_id', $store->id)->where('product_id', $product->id)->first()->quantity);
    }

    public function test_insufficient_stock_throws_exception()
    {
        $branch = Branch::create(['name' => 'Test Branch']);
        $store = Store::create(['branch_id' => $branch->id, 'name' => 'Test Store']);
        $product = Product::create(['sku' => 'TEST-01', 'name' => 'Test Product']);

        Stock::create(['store_id' => $store->id, 'product_id' => $product->id, 'quantity' => 5]);

        $this->expectException(InsufficientStockException::class);
        $this->service->recordSale($product, $store, 10);
    }
}
