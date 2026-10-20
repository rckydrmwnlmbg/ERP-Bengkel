<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Models\Part;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('inventory.manage');

        $query = Purchase::with(['supplier', 'creator'])->withCount('items');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $purchases = $query->orderByDesc('purchase_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        Gate::authorize('inventory.manage');

        $suppliers = Supplier::orderBy('name')->get();
        $parts = Part::where('is_active', true)->orderBy('name')->get();

        return view('purchases.create', compact('suppliers', 'parts'));
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $itemsData = $request->input('items', []);

        // Hitung total nilai pembelian
        $total = 0;
        foreach ($itemsData as $item) {
            $total += ((int) $item['qty'] * (int) $item['unit_cost']);
        }

        $purchase = DB::transaction(function () use ($request, $itemsData, $total) {
            $purchase = Purchase::create([
                'supplier_id' => $request->input('supplier_id'),
                'purchase_date' => $request->input('purchase_date'),
                'reference' => $request->input('reference'),
                'total' => $total,
                'created_by' => Auth::id(),
            ]);

            foreach ($itemsData as $item) {
                $qty = (int) $item['qty'];
                $unitCost = (int) $item['unit_cost'];
                $partId = (int) $item['part_id'];

                // Simpan item pembelian
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'part_id' => $partId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                ]);

                // BR-S3 / BR-S7: Tambah stok dan perbarui Weighted Average Cost lewat StockService
                $this->stockService->recordPurchaseIn(
                    partId: $partId,
                    qty: $qty,
                    unitCost: $unitCost,
                    purchaseId: $purchase->id,
                    reference: $purchase->reference,
                    userId: Auth::id()
                );
            }

            return $purchase;
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Penerimaan barang masuk berhasil dicatat. Stok dan HPP rata-rata sparepart telah diperbarui.');
    }

    public function show(Purchase $purchase): View
    {
        Gate::authorize('inventory.manage');

        $purchase->load(['supplier', 'items.part', 'creator']);

        return view('purchases.show', compact('purchase'));
    }
}
