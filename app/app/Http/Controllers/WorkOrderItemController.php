<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidWorkOrderOperationException;
use App\Http\Requests\WorkOrder\AddPartItemRequest;
use App\Http\Requests\WorkOrder\AddServiceItemRequest;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WorkOrderItemController extends Controller
{
    public function __construct(
        protected WorkOrderService $workOrderService
    ) {}

    public function storePart(AddPartItemRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('service-orders.manage');

        try {
            $this->workOrderService->addPartItem(
                workOrder: $workOrder,
                partId: (int) $request->input('part_id'),
                qty: (int) $request->input('qty'),
                overrideReason: $request->input('override_reason'),
                userId: Auth::id()
            );

            return redirect()->route('work-orders.show', $workOrder)
                ->with('success', 'Sparepart berhasil ditambahkan ke order servis dan stok telah dikurangi.');
        } catch (InsufficientStockException $e) {
            return redirect()->route('work-orders.show', $workOrder)
                ->withErrors(['part_id' => $e->getMessage()]);
        } catch (InvalidWorkOrderOperationException $e) {
            return redirect()->route('work-orders.show', $workOrder)
                ->withErrors(['error' => $e->getMessage()]);
        } catch (ValidationException $e) {
            return redirect()->route('work-orders.show', $workOrder)
                ->withErrors($e->validator);
        }
    }

    public function storeService(AddServiceItemRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('service-orders.manage');

        try {
            $this->workOrderService->addServiceItem(
                workOrder: $workOrder,
                serviceId: (int) $request->input('service_id'),
                qty: (int) $request->input('qty', 1),
                userId: Auth::id()
            );

            return redirect()->route('work-orders.show', $workOrder)
                ->with('success', 'Jasa servis berhasil ditambahkan ke order servis.');
        } catch (InvalidWorkOrderOperationException $e) {
            return redirect()->route('work-orders.show', $workOrder)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(WorkOrder $workOrder, WorkOrderItem $item): RedirectResponse
    {
        Gate::authorize('service-orders.manage');

        try {
            $this->workOrderService->removeItem(
                workOrder: $workOrder,
                itemId: $item->id,
                userId: Auth::id()
            );

            return redirect()->route('work-orders.show', $workOrder)
                ->with('success', 'Item berhasil dihapus dari order servis.');
        } catch (InvalidWorkOrderOperationException $e) {
            return redirect()->route('work-orders.show', $workOrder)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
