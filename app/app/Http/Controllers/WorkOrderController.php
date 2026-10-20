<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidWorkOrderOperationException;
use App\Http\Requests\WorkOrder\StoreWorkOrderRequest;
use App\Http\Requests\WorkOrder\TransitionStatusRequest;
use App\Http\Requests\WorkOrder\UpdateWorkOrderRequest;
use App\Models\Customer;
use App\Models\EquipmentUnit;
use App\Models\Part;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function __construct(
        protected WorkOrderService $workOrderService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('service-orders.view');

        $query = WorkOrder::with(['customer', 'equipmentUnit', 'mechanic'])
            ->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('wo_number', 'like', "%{$search}%")
                  ->orWhere('complaint', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('equipmentUnit', fn ($uq) => $uq->where('unit_code', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('mechanic_id')) {
            $query->where('mechanic_id', $request->input('mechanic_id'));
        }

        $workOrders = $query->orderByDesc('opened_at')->orderByDesc('id')->paginate(15)->withQueryString();
        $mechanics = User::whereIn('role', ['mechanic', 'admin'])->where('is_active', true)->orderBy('name')->get();

        return view('work_orders.index', compact('workOrders', 'mechanics'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('service-orders.manage');

        $customers = Customer::with('equipmentUnits')->orderBy('name')->get();
        $mechanics = User::whereIn('role', ['mechanic', 'admin'])->where('is_active', true)->orderBy('name')->get();
        $selectedCustomerId = $request->input('customer_id');

        return view('work_orders.create', compact('customers', 'mechanics', 'selectedCustomerId'));
    }

    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        Gate::authorize('service-orders.manage');

        $workOrder = $this->workOrderService->createWorkOrder(
            $request->validated(),
            Auth::id()
        );

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', "Order servis {$workOrder->wo_number} berhasil dibuat.");
    }

    public function show(WorkOrder $workOrder): View
    {
        Gate::authorize('service-orders.view');

        $workOrder->load([
            'customer.serviceRates',
            'equipmentUnit',
            'mechanic',
            'creator',
            'items.part',
            'items.service',
        ]);

        $availableParts = Part::where('is_active', true)->orderBy('name')->get();
        $availableServices = Service::orderBy('name')->get();

        return view('work_orders.show', compact('workOrder', 'availableParts', 'availableServices'));
    }

    public function edit(WorkOrder $workOrder): View
    {
        Gate::authorize('service-orders.manage');

        if ($workOrder->status === 'invoiced') {
            abort(403, 'Order servis yang telah ditagihkan (invoiced) tidak dapat diedit.');
        }

        $mechanics = User::whereIn('role', ['mechanic', 'admin'])->where('is_active', true)->orderBy('name')->get();

        return view('work_orders.edit', compact('workOrder', 'mechanics'));
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('service-orders.manage');

        if ($workOrder->status === 'invoiced') {
            return back()->withErrors(['error' => 'Order servis yang telah ditagihkan tidak dapat diubah (BR-W3).']);
        }

        $workOrder->update($request->validated());

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', "Informasi keluhan & diagnosa order servis {$workOrder->wo_number} berhasil diperbarui.");
    }

    public function transitionStatus(TransitionStatusRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('service-orders.manage');

        try {
            $this->workOrderService->transitionStatus(
                $workOrder,
                $request->input('status'),
                Auth::id()
            );

            $statusLabels = [
                'in_progress' => 'Sedang Dikerjakan (In Progress)',
                'completed' => 'Selesai Dikerjakan (Completed)',
                'cancelled' => 'Dibatalkan (Cancelled)',
            ];
            $label = $statusLabels[$request->input('status')] ?? $request->input('status');

            return redirect()->route('work-orders.show', $workOrder)
                ->with('success', "Status order servis berhasil diubah menjadi {$label}.");
        } catch (InvalidWorkOrderOperationException $e) {
            return redirect()->route('work-orders.show', $workOrder)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
