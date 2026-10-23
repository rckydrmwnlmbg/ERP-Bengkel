<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Services\AuditService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function show(Customer $customer, Request $request, ReportService $reportService): View
    {
        Gate::authorize('master-data.view');

        $customer->load([
            'equipmentUnits' => fn ($q) => $q->orderBy('unit_code'),
            'serviceRates.service',
        ]);

        $services = Service::where('is_active', true)->orderBy('name')->get();

        // (a) Ringkasan: total piutang, jumlah invoice terbuka, total transaksi 12 bulan (BR-R8)
        $receivablesData = $reportService->getCustomerReceivables($customer);
        $totalReceivable = (int) $receivablesData['total_receivable'];
        $openInvoicesCount = (int) $receivablesData['open_invoices_count'];

        $twelveMonthsAgo = Carbon::now()->subMonths(12)->startOfDay();
        $totalTransactions12m = (int) $customer->invoices()
            ->where('status', '!=', 'void')
            ->whereDate('issue_date', '>=', $twelveMonthsAgo->toDateString())
            ->sum('total');

        // (b) Tabel riwayat order servis dan invoice (nomor, tanggal, jenis, total, sisa, status) terurut terbaru, dengan paginasi
        $workOrders = $customer->workOrders()
            ->with(['items', 'activeInvoice.validPayments'])
            ->get()
            ->map(function (WorkOrder $wo) {
                $total = (int) $wo->items->sum('line_total');
                $remaining = $wo->activeInvoice ? $wo->activeInvoice->remainingBalance() : null;

                return (object) [
                    'type' => 'work_order',
                    'id' => $wo->id,
                    'number' => $wo->wo_number,
                    'date' => $wo->opened_at ?: $wo->created_at,
                    'type_label' => 'Order Servis',
                    'total' => $total,
                    'remaining' => $remaining,
                    'status' => $wo->status,
                    'url' => route('work-orders.show', $wo),
                    'timestamp' => ($wo->opened_at ?: $wo->created_at)?->timestamp ?? 0,
                ];
            });

        $invoices = $customer->invoices()
            ->with('validPayments')
            ->get()
            ->map(function (Invoice $inv) {
                return (object) [
                    'type' => 'invoice',
                    'id' => $inv->id,
                    'number' => $inv->invoice_number,
                    'date' => $inv->issue_date ?: $inv->created_at,
                    'type_label' => 'Invoice',
                    'total' => (int) $inv->total,
                    'remaining' => $inv->remainingBalance(),
                    'status' => $inv->status,
                    'url' => route('invoices.show', $inv),
                    'timestamp' => ($inv->issue_date ?: $inv->created_at)?->timestamp ?? 0,
                ];
            });

        $allHistory = $workOrders->concat($invoices)->sortByDesc(function ($item) {
            return [$item->timestamp, $item->id];
        })->values();

        $historyPage = max(1, (int) $request->input('history_page', 1));
        $perPage = 10;
        $paginatedHistory = new LengthAwarePaginator(
            $allHistory->slice(($historyPage - 1) * $perPage, $perPage)->values(),
            $allHistory->count(),
            $perPage,
            $historyPage,
            ['path' => $request->url(), 'pageName' => 'history_page']
        );
        $paginatedHistory->withQueryString();

        // (c) Riwayat pembayaran terurut terbaru, dengan paginasi
        $payments = Payment::whereHas('invoice', fn ($q) => $q->where('customer_id', $customer->id))
            ->with(['invoice', 'creator'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'payments_page')
            ->withQueryString();

        return view('customers.show', compact(
            'customer',
            'services',
            'totalReceivable',
            'openInvoicesCount',
            'totalTransactions12m',
            'paginatedHistory',
            'payments'
        ));
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
