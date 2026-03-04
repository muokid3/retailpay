<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $branchManager;
    private $storeManager;
    private $branch;
    private $store;
    private $otherBranch;
    private $otherStore;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();

        //Setup Business Structure
        $this->branch = Branch::create(['name' => 'Main Branch']);
        $this->store = Store::create(['branch_id' => $this->branch->id, 'name' => 'Main Store']);

        $this->otherBranch = Branch::create(['name' => 'Other Branch']);
        $this->otherStore = Store::create(['branch_id' => $this->otherBranch->id, 'name' => 'Other Store']);

        $this->product = Product::create(['sku' => 'SKU-1', 'name' => 'Prod 1']);

        //Create Users
        $this->admin = User::factory()->create(['role' => 'administrator']);

        $this->branchManager = User::factory()->create([
            'role' => 'branch_manager',
            'branch_id' => $this->branch->id,
        ]);

        $this->storeManager = User::factory()->create([
            'role' => 'store_manager',
            'branch_id' => $this->branch->id,
            'store_id' => $this->store->id,
        ]);
    }

    public function test_admin_can_access_anything()
    {
        $stock = Stock::create(['store_id' => $this->otherStore->id, 'product_id' => $this->product->id, 'quantity' => 10]);

        $this->assertTrue($this->admin->can('view', $stock));
        $this->assertTrue($this->admin->can('update', $stock));
        $this->assertTrue($this->admin->can('moveFrom', $this->otherStore));
        $this->assertTrue($this->admin->can('moveTo', $this->otherStore));
    }

    public function test_branch_manager_can_only_access_their_branch()
    {
        $myStock = Stock::create(['store_id' => $this->store->id, 'product_id' => $this->product->id, 'quantity' => 10]);
        $otherStock = Stock::create(['store_id' => $this->otherStore->id, 'product_id' => $this->product->id, 'quantity' => 10]);

        // Access internal
        $this->assertTrue($this->branchManager->can('view', $myStock));
        $this->assertTrue($this->branchManager->can('update', $myStock));
        $this->assertTrue($this->branchManager->can('moveFrom', $this->store));
        $this->assertTrue($this->branchManager->can('moveTo', $this->store));

        // Access external
        $this->assertFalse($this->branchManager->can('view', $otherStock));
        $this->assertFalse($this->branchManager->can('update', $otherStock));
        $this->assertFalse($this->branchManager->can('moveFrom', $this->otherStore));
        $this->assertFalse($this->branchManager->can('moveTo', $this->otherStore));
    }

    public function test_store_manager_can_only_access_their_store()
    {
        // Add another store in the same branch to test isolation
        $storeAlias = Store::create(['branch_id' => $this->branch->id, 'name' => 'Alias Store']);

        $myStock = Stock::create(['store_id' => $this->store->id, 'product_id' => $this->product->id, 'quantity' => 10]);
        $aliasStock = Stock::create(['store_id' => $storeAlias->id, 'product_id' => $this->product->id, 'quantity' => 10]);

        // Access internal
        $this->assertTrue($this->storeManager->can('view', $myStock));
        $this->assertTrue($this->storeManager->can('update', $myStock));

        // Access another store even in same branch
        $this->assertFalse($this->storeManager->can('view', $aliasStock));
        $this->assertFalse($this->storeManager->can('update', $aliasStock));
        $this->assertFalse($this->storeManager->can('moveFrom', $storeAlias));
    }
}
