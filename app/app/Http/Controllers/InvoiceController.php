<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidInvoiceOperationException;
use App\Http\Requests\Invoice\StoreDirectSaleRequest;
use App\Http\Requests\Invoice\StoreWorkOrderInvoiceRequest;
use App\Http\Requests\Invoice\VoidInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Setting;
use App\Models\WorkOrder;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('invoices.manage');

        $query = Invoice::with(['customer', 'validPayments'])->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->input('source_type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->orderByDesc('issue_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function createFromWorkOrder(WorkOrder $workOrder): View
    {
        Gate::authorize('invoices.manage');

        if ($workOrder->status !== 'completed') {
            abort(400, 'Invoice hanya dapat diterbitkan untuk order servis yang sudah selesai dikerjakan (completed).');
        }

        if ($workOrder->activeInvoice()->exists()) {
            return redirect()->route('invoices.show', $workOrder->activeInvoice)
                ->with('warning', 'Order servis ini telah memiliki invoice aktif.');
        }

        $workOrder->load(['customer', 'equipmentUnit', 'items']);
        $taxRate = Setting::getTaxRate();

        return view('invoices.create_from_wo', compact('workOrder', 'taxRate'));
    }

    public function storeFromWorkOrder(StoreWorkOrderInvoiceRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('invoices.manage');

        try {
            $invoice = $this->invoiceService->createFromWorkOrder(
                workOrder: $workOrder,
                params: $request->validated(),
                userId: Auth::id()
            );

            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_number} berhasil diterbitkan.");
        } catch (InvalidInvoiceOperationException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function createDirectSale(): View
    {
        Gate::authorize('direct-sales.manage');

        $customers = Customer::orderBy('name')->get();
        $defaultCustomer = Customer::where('code', 'CUST-UMUM')->first() ?? $customers->first();
        $parts = Part::where('is_active', true)->where('qty_on_hand', '>', 0)->orderBy('name')->get();
        $taxRate = Setting::getTaxRate();

        return view('invoices.create_direct_sale', compact('customers', 'defaultCustomer', 'parts', 'taxRate'));
    }

    public function storeDirectSale(StoreDirectSaleRequest $request): RedirectResponse
    {
        Gate::authorize('direct-sales.manage');

        try {
            $invoice = $this->invoiceService->createDirectSale(
                data: $request->validated(),
                userId: Auth::id()
            );

            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Penjualan langsung berhasil diproses (Invoice {$invoice->invoice_number}). Stok telah dikurangi.");
        } catch (InsufficientStockException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        } catch (InvalidInvoiceOperationException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Invoice $invoice): View
    {
        Gate::authorize('invoices.manage');

        $invoice->load([
            'customer',
            'workOrder.equipmentUnit',
            'items.part',
            'items.service',
            'payments.creator',
            'creator',
            'voidedBy',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function print(Invoice $invoice): View
    {
        Gate::authorize('invoices.manage');

        $invoice->load([
            'customer',
            'workOrder.equipmentUnit',
            'items.part',
            'items.service',
            'validPayments',
            'creator',
        ]);

        $shopName = Setting::get('shop_name', 'Bengkel Alat Berat & Toko Sparepart');

        return view('invoices.print', compact('invoice', 'shopName'));
    }

    public function void(VoidInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('void-financial');

        try {
            $this->invoiceService->voidInvoice(
                invoice: $invoice,
                reason: $request->input('reason'),
                userId: Auth::id()
            );

            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_number} berhasil dibatalkan (void).");
        } catch (InvalidInvoiceOperationException $e) {
            return redirect()->route('invoices.show', $invoice)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
