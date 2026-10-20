<?php

namespace App\Http\Controllers;

use App\Http\Requests\Part\StorePartRequest;
use App\Http\Requests\Part\UpdatePartRequest;
use App\Models\Part;
use App\Services\AuditService;
use App\Services\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PartController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
        protected AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('master-data.view');

        $query = Part::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $categories = Part::select('category')->distinct()->pluck('category');
        $parts = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('parts.index', compact('parts', 'categories'));
    }

    public function create(): View
    {
        Gate::authorize('master-data.manage');

        return view('parts.create');
    }

    public function store(StorePartRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // Tanpa pergerakan stok dulu: stok awal wajib 0, pengisian stok lewat modul stok (Fase 3)
        $data['qty_on_hand'] = 0;
        $data['avg_cost'] = 0;
        $data['is_active'] = $request->boolean('is_active', true);

        $part = Part::create($data);

        $this->auditService->log(
            action: 'part_created',
            entityType: 'Part',
            entityId: $part->id,
            oldValues: null,
            newValues: [
                'sku' => $part->sku,
                'name' => $part->name,
                'sell_price' => $part->sell_price,
            ]
        );

        return redirect()->route('parts.index')
            ->with('success', "Sparepart {$part->name} ({$part->sku}) berhasil ditambahkan.");
    }

    public function show(Part $part): View
    {
        Gate::authorize('master-data.view');

        return view('parts.show', compact('part'));
    }

    public function edit(Part $part): View
    {
        Gate::authorize('master-data.manage');

        return view('parts.edit', compact('part'));
    }

    public function update(UpdatePartRequest $request, Part $part): RedirectResponse
    {
        $newSellPrice = (int) $request->input('sell_price');
        $overrideReason = $request->input('override_reason');

        // Update harga melalui PricingService (validasi BR-P4 dan pencatatan audit log jika berubah)
        $this->pricingService->updatePartPrice($part, $newSellPrice, $overrideReason);

        // Update atribut lainnya
        $part->sku = $request->input('sku');
        $part->name = $request->input('name');
        $part->category = $request->input('category');
        $part->unit = $request->input('unit');
        $part->min_stock = (int) $request->input('min_stock');
        $part->is_active = $request->boolean('is_active');
        $part->save();

        return redirect()->route('parts.index')
            ->with('success', "Data sparepart {$part->name} berhasil diperbarui.");
    }

    public function destroy(Part $part): RedirectResponse
    {
        Gate::authorize('master-data.manage');

        $partName = $part->name;
        $part->delete();

        return redirect()->route('parts.index')
            ->with('success', "Sparepart {$partName} berhasil dihapus (soft delete).");
    }
}
