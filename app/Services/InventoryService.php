<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    /**
     * move stock between stores or adjust stock levels.
     */
    public function move(
        Product $product,
        ?Store  $source = null,
        ?Store  $destination = null,
        int     $quantity,
        string  $type,
        ?string $reference = null,
        ?User   $user = null
    ): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantity must be greater than zero.");
        }

        $user = $user ?? Auth::user();

        DB::transaction(function () use ($product, $source, $destination, $quantity, $type, $reference, $user) {
            // handle Source - Deduct
            if ($source) {
                $sourceStock = Stock::where('store_id', $source->id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate() // concurrency protection so other trx dont modify until we're done
                    ->first();

                if (!$sourceStock || $sourceStock->quantity < $quantity) {
                    throw new InsufficientStockException(
                        "Insufficient stock for SKU: {$product->sku} in store: {$source->name}. Current: " . ($sourceStock->quantity ?? 0)
                    );
                }

                $sourceStock->decrement('quantity', $quantity);
            }

            // handle Destination - Add
            if ($destination) {

                //retrieveing existing based on store and product, if not exist create with 0 quantity
                $destinationStock = Stock::firstOrCreate(
                    ['store_id' => $destination->id, 'product_id' => $product->id],
                    ['quantity' => 0]
                );

                // lock the destination stock row for update - if not just created
                if (!$destinationStock->wasRecentlyCreated) {
                    $destinationStock = Stock::where('id', $destinationStock->id)->lockForUpdate()->first();
                }

                $destinationStock->increment('quantity', $quantity);
            }

            // record movement - audit log
            StockMovement::create([
                'product_id' => $product->id,
                'source_store_id' => $source?->id,
                'destination_store_id' => $destination?->id,
                'quantity' => $quantity,
                'type' => $type,
                'reference' => $reference,
                'user_id' => $user?->id,
            ]);
        });
    }

    /**
     * Record a Sale (Source: Store, Destination: NULL -> External)
     */
    public function recordSale(Product $product, Store $store, int $quantity, ?string $orderId = null): void
    {
        $this->move($product, $store, null, $quantity, 'sale', $orderId);
    }

    /**
     * Record a Transfer (Source: Store A, Destination: Store B)
     */
    public function transferStock(Product $product, Store $source, Store $destination, int $quantity, ?string $reference = null): void
    {
        $this->move($product, $source, $destination, $quantity, 'transfer', $reference);
    }

    /**
     * Record Procurement (Source: External -> NULL, Destination: Store)
     */
    public function recordProcurement(Product $product, Store $store, int $quantity, ?string $invoice = null): void
    {
        $this->move($product, null, $store, $quantity, 'procurement', $invoice);
    }

    /**
     * Record Stock Adjustment (Manual override)
     */
    public function adjustStock(Product $product, Store $store, int $quantity, string $reason, ?string $reference = null): void
    {
        // For adjustments, if quantity is negative, we treat it as a deduction from source
        // if positive, we treat it as addition to destination.
        // IMPL: we make it simple -> use 'adjustment' type and handle sign.
        if ($quantity > 0) {
            // add to destination
            $this->move($product, null, $store, $quantity, 'adjustment', $reference ?? $reason);
        } elseif ($quantity < 0) {
            // deduct from source
            $this->move($product, $store, null, abs($quantity), 'adjustment', $reference ?? $reason);
        }
    }
}
