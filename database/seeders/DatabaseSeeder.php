<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //Create Branches
        $branchLav = Branch::create(['name' => 'Lavington Branch']);
        $branchWest = Branch::create(['name' => 'Westlands Branch']);

        //Create Stores
        $storeLav1 = Store::create(['branch_id' => $branchLav->id, 'name' => 'Lavington Store']);
        $storeWest1 = Store::create(['branch_id' => $branchWest->id, 'name' => 'Westgate Store']);
        $storeWest2 = Store::create(['branch_id' => $branchWest->id, 'name' => 'Sarit Store']);

        //Create 10 random Products
        $products = [];
        for ($i = 1; $i <= 10; $i++) {
            $products[] = Product::create([
                'sku' => "SKU-00{$i}",
                'name' => "Product {$i}",
                'description' => "Description for Product {$i}",
                'price' => rand(100, 5000) / 100,
            ]);
        }

        //Create Users

        // Admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@retailpay.test',
            'password' => Hash::make('adminpass123'),
            'role' => 'administrator',
        ]);

        // Branch Managers
        User::create([
            'name' => 'Lavington Manager',
            'email' => 'lavington@retailpay.test',
            'password' => Hash::make('lavington'),
            'role' => 'branch_manager',
            'branch_id' => $branchLav->id,
        ]);

        User::create([
            'name' => 'Westlands Manager',
            'email' => 'westlands@retailpay.test',
            'password' => Hash::make('westlands'),
            'role' => 'branch_manager',
            'branch_id' => $branchWest->id,
        ]);

        // Store Managers
        $stores = [$storeLav1, $storeWest1, $storeWest2];
        foreach ($stores as $index => $store) {
            User::create([
                'name' => "Manager {$store->name}",
                'email' => "manager." . strtolower($store->name) . "@retailpay.test",
                'password' => Hash::make('password'),
                'role' => 'store_manager',
                'branch_id' => $store->branch_id,
                'store_id' => $store->id,
            ]);
        }

        //Initial Stock (Procurement simulation)
        foreach ($products as $product) {
            foreach ($stores as $store) {
                $qty = rand(50, 100);
                Stock::create([
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'destination_store_id' => $store->id,
                    'quantity' => $qty,
                    'type' => 'procurement',
                    'reference' => 'INITIAL-STOCK',
                ]);
            }
        }
    }
}
