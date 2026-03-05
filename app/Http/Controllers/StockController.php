<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StockController extends Controller
{
    private $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display the inventory dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        // Query filtering based on roles.
        // eager load relationships for to avoind n+1 issues in the view.
        $query = Stock::with(['store.branch', 'product']);

        if ($user->isBranchManager()) {
            $query->whereHas('store', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        } elseif ($user->isStoreManager()) {
            $query->where('store_id', $user->store_id);
        }

        $stocks = $query->paginate(10);

        // Recent movements
        // again, eager load relationships for performance, avoid n+1
        $movementQuery = StockMovement::with(['product', 'sourceStore', 'destinationStore', 'user'])->latest();

        if ($user->isBranchManager()) {
            $movementQuery->where(function ($q) use ($user) {
                $q->whereHas('sourceStore', fn($sq) => $sq->where('branch_id', $user->branch_id))
                    ->orWhereHas('destinationStore', fn($sq) => $sq->where('branch_id', $user->branch_id));
            });
        } elseif ($user->isStoreManager()) {
            $movementQuery->where(function ($q) use ($user) {
                $q->where('source_store_id', $user->store_id)
                    ->orWhere('destination_store_id', $user->store_id);
            });
        }

        //just get the latest 10 mvts for the dashboard,
        // with all related data eager loaded for performance
        $movements = $movementQuery->limit(10)->get();

        // Summary Stats
        $stats = [
            'total_skus' => Product::count(),
            'total_stock' => $query->clone()->sum('quantity'),
        ];

        if ($user->isAdministrator()) {
            $stats['branches_count'] = Branch::count();
            $stats['stores_count'] = Store::count();
        } elseif ($user->isBranchManager()) {
            $stats['stores_count'] = Store::where('branch_id', $user->branch_id)->count();
        }

        return view('inventory.index', compact('stocks', 'movements', 'stats'));
    }

    /**
     * Show movement form.
     */
    public function create()
    {
        Gate::authorize('create', Stock::class);

        $user = Auth::user();
        $products = Product::all();

        // Scope available stores for source/destination
        if ($user->isAdministrator()) {
            $stores = Store::with('branch')->get();
        } elseif ($user->isBranchManager()) {
            $stores = Store::with('branch')->where('branch_id', $user->branch_id)->get();
        } elseif ($user->isStoreManager()) {
            $stores = Store::with('branch')->where('branch_id', $user->branch_id)->get();
        }

        return view('inventory.move', compact('products', 'stores'));
    }

    /**
     * Process movement.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:sale,transfer,adjustment,procurement',
            'quantity' => 'required|integer|min:1',
            'source_store_id' => 'nullable|exists:stores,id',
            'destination_store_id' => 'nullable|exists:stores,id',
            'reference' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($request->product_id);
        $source = $request->source_store_id ? Store::findOrFail($request->source_store_id) : null;
        $destination = $request->destination_store_id ? Store::findOrFail($request->destination_store_id) : null;

        // Authorization checks
        if ($source) {
            Gate::authorize('moveFrom', $source);
        }
        if ($destination) {
            Gate::authorize('moveTo', $destination);
        }

        try {
            $this->inventoryService->move(
                $product,
                $source,
                $destination,
                $request->quantity,
                $request->type,
                $request->reference
            );
            return redirect()->route('inventory.index')->with('success', 'Stock movement recorded successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
