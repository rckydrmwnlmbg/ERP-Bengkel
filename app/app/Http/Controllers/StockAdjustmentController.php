<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\StoreStockAdjustmentRequest;
use App\Models\Part;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('inventory.manage');

        $query = StockMovement::with(['part', 'creator'])
            ->whereIn('type', ['ADJUSTMENT_IN', 'ADJUSTMENT_OUT']);

        if ($request->filled('part_id')) {
            $query->where('part_id', $request->input('part_id'));
        }

        $adjustments = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('stock_adjustments.index', compact('adjustments'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('inventory.manage');

        $selectedPartId = $request->input('part_id');
        $parts = Part::where('is_active', true)->orderBy('name')->get();

        return view('stock_adjustments.create', compact('parts', 'selectedPartId'));
    }

    public function store(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $partId = (int) $request->input('part_id');
        $type = $request->input('type');
        $qty = (int) $request->input('qty');
        $reason = $request->input('reason');

        try {
            $movement = $this->stockService->recordAdjustment(
                partId: $partId,
                type: $type,
                qty: $qty,
                reason: $reason,
                userId: Auth::id()
            );

            return redirect()->route('stock-adjustments.index')
                ->with('success', "Penyesuaian stok berhasil dicatat (Saldo baru: {$movement->balance_after} unit).");
        } catch (\App\Exceptions\InsufficientStockException $e) {
            return back()->withInput()->withErrors(['qty' => $e->getMessage()]);
        }
    }
}
