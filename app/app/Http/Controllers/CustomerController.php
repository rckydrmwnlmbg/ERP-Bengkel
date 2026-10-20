<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Service;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}
    public function index(Request $request): View
    {
        Gate::authorize('master-data.view');

        $query = Customer::query()->withCount('equipmentUnits');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $customers = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        Gate::authorize('customer.create');

        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        $this->auditService->log(
            action: 'CUSTOMER_CREATE',
            entityType: 'Customer',
            entityId: $customer->id,
            oldValues: null,
            newValues: ['name' => $customer->name, 'code' => $customer->code, 'type' => $customer->type],
            userId: Auth::id()
        );

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Customer $customer): View
    {
        Gate::authorize('master-data.view');

        $customer->load([
            'equipmentUnits' => fn ($q) => $q->orderBy('unit_code'),
            'serviceRates.service',
        ]);

        $services = Service::where('is_active', true)->orderBy('name')->get();

        return view('customers.show', compact('customer', 'services'));
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('master-data.manage');

        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $oldValues = $customer->only(['name', 'type', 'phone', 'address', 'payment_term_days']);
        $customer->update($request->validated());
        $newValues = $customer->only(['name', 'type', 'phone', 'address', 'payment_term_days']);

        $this->auditService->log(
            action: 'CUSTOMER_UPDATE',
            entityType: 'Customer',
            entityId: $customer->id,
            oldValues: $oldValues,
            newValues: $newValues,
            userId: Auth::id()
        );

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('master-data.manage');

        $this->auditService->log(
            action: 'CUSTOMER_DELETE',
            entityType: 'Customer',
            entityId: $customer->id,
            oldValues: ['name' => $customer->name, 'code' => $customer->code],
            newValues: ['deleted_at' => now()->toDateTimeString()],
            userId: Auth::id()
        );

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil dihapus (soft delete).');
    }
}
