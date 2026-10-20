<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PartStockCardController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function show(Part $part): View
    {
        Gate::authorize('master-data.view');

        $movements = $part->stockMovements()
            ->with('creator')
            ->orderBy('id', 'desc')
            ->paginate(25);

        // Invariant check per BR-S1
        $isIntegrityValid = $this->stockService->verifyStockIntegrity($part->id);
        $totalSumChange = (int) $part->stockMovements()->sum('qty_change');

        return view('parts.stock_card', compact('part', 'movements', 'isIntegrityValid', 'totalSumChange'));
    }
}
