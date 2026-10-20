<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}
    public function index(Request $request): View
    {
        Gate::authorize('master-data.view');

        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        Gate::authorize('master-data.manage');

        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        $this->auditService->log(
            action: 'SUPPLIER_CREATE',
            entityType: 'Supplier',
            entityId: $supplier->id,
            oldValues: null,
            newValues: ['name' => $supplier->name, 'phone' => $supplier->phone],
            userId: Auth::id()
        );

        return redirect()->route('suppliers.index')
            ->with('success', "Pemasok {$supplier->name} berhasil ditambahkan.");
    }

    public function edit(Supplier $supplier): View
    {
        Gate::authorize('master-data.manage');

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $oldValues = $supplier->only(['name', 'phone', 'address']);
        $supplier->update($request->validated());
        $newValues = $supplier->only(['name', 'phone', 'address']);

        $this->auditService->log(
            action: 'SUPPLIER_UPDATE',
            entityType: 'Supplier',
            entityId: $supplier->id,
            oldValues: $oldValues,
            newValues: $newValues,
            userId: Auth::id()
        );

        return redirect()->route('suppliers.index')
            ->with('success', "Data pemasok {$supplier->name} berhasil diperbarui.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('master-data.manage');

        $supplierName = $supplier->name;
        $this->auditService->log(
            action: 'SUPPLIER_DELETE',
            entityType: 'Supplier',
            entityId: $supplier->id,
            oldValues: ['name' => $supplierName],
            newValues: null,
            userId: Auth::id()
        );

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', "Pemasok {$supplierName} berhasil dihapus.");
    }
}
