<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * BR-R1: Omzet = Σ invoices.total dengan issue_date dalam periode (inklusif).
     * Invoice void dikecualikan dari semua laporan.
     */
    public function getRevenueReport(string $startDate, string $endDate): array
    {
        $invoices = Invoice::with(['customer', 'validPayments'])
            ->where('status', '!=', 'void')
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->orderBy('issue_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $subtotal = (int) $invoices->sum('subtotal');
        $discountTotal = (int) $invoices->sum('discount_amount');
        $taxTotal = (int) $invoices->sum('tax_amount');
        $totalRevenue = (int) $invoices->sum('total');

        $bySource = [
            'work_order' => (int) $invoices->where('source_type', 'work_order')->sum('total'),
            'counter_sale' => (int) $invoices->where('source_type', 'counter_sale')->sum('total'),
        ];

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'invoices' => $invoices,
            'invoice_count' => $invoices->count(),
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total_revenue' => $totalRevenue,
            'by_source' => $bySource,
        ];
    }

    /**
     * BR-R2, BR-R3: Laba Rugi Sederhana.
     * - Omzet = Σ invoices.total dalam periode (BR-R1).
     * - HPP = Σ (qty × unit_cost_snapshot) pada item part dari invoice dalam periode (BR-R2).
     *   Jasa dianggap biaya 0 (penyederhanaan demo, BR-R2).
     * - Laba kotor = Omzet − HPP (BR-R3).
     * - Beban operasional = Σ expenses.amount dalam periode.
     * - Laba bersih sederhana = Laba kotor − Beban operasional (BR-R3).
     */
    public function getProfitLossReport(string $startDate, string $endDate): array
    {
        // 1. Omzet
        $revenueData = $this->getRevenueReport($startDate, $endDate);
        $totalRevenue = $revenueData['total_revenue'];
        $invoiceIds = $revenueData['invoices']->pluck('id')->toArray();

        // 2. HPP (BR-R2)
        // Hitung HPP dari baris item bertipe 'part' pada seluruh invoice dalam periode
        $partItems = InvoiceItem::whereIn('invoice_id', $invoiceIds)
            ->where('item_type', 'part')
            ->get();

        $totalHpp = (int) $partItems->sum(function (InvoiceItem $item) {
            return (int) $item->qty * (int) $item->unit_cost_snapshot;
        });

        // 3. Laba Kotor (BR-R3)
        $grossProfit = $totalRevenue - $totalHpp;

        // 4. Beban Operasional
        $expenses = Expense::whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate)
            ->orderBy('expense_date', 'asc')
            ->get();

        $totalExpenses = (int) $expenses->sum('amount');

        // Beban operasional per kategori
        $expensesByCategory = $expenses->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => $category,
                'total' => (int) $items->sum('amount'),
                'count' => $items->count(),
            ];
        })->values();

        // 5. Laba Bersih Sederhana (BR-R3)
        $netProfit = $grossProfit - $totalExpenses;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_revenue' => $totalRevenue,
            'total_hpp' => $totalHpp,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'expenses_by_category' => $expensesByCategory,
            'expenses' => $expenses,
            'simplification_notes' => [
                'br_r2' => 'Jasa dianggap berbiaya HPP Rp 0 (penyederhanaan demo).',
            ],
        ];
    }

    /**
     * BR-R4, BR-R5: Arus Kas.
     * - Arus kas masuk = Σ pembayaran valid dengan paid_at dalam periode (BR-R4).
     * - Arus kas keluar = Σ purchases.total (dianggap tunai di tanggal pembelian) + Σ expenses.amount dalam periode (BR-R5).
     *   Hutang supplier di luar scope; tulis penyederhanaan ini di UI (BR-R5).
     * - Kas bersih = Kas Masuk − Kas Keluar.
     */
    public function getCashFlowReport(string $startDate, string $endDate): array
    {
        // 1. Arus Kas Masuk (BR-R4)
        $payments = Payment::with('invoice.customer')
            ->where('status', 'valid')
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->orderBy('paid_at', 'asc')
            ->get();

        $cashIn = (int) $payments->sum('amount');

        $cashInByMethod = [
            'cash' => (int) $payments->where('method', 'cash')->sum('amount'),
            'transfer' => (int) $payments->where('method', 'transfer')->sum('amount'),
            'other' => (int) $payments->where('method', 'other')->sum('amount'),
        ];

        // 2. Arus Kas Keluar (BR-R5)
        // a. Pembelian barang masuk (dianggap tunai)
        $purchases = Purchase::with('supplier')
            ->whereDate('purchase_date', '>=', $startDate)
            ->whereDate('purchase_date', '<=', $endDate)
            ->orderBy('purchase_date', 'asc')
            ->get();

        $totalPurchases = (int) $purchases->sum('total');

        // b. Beban operasional
        $expenses = Expense::whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate)
            ->orderBy('expense_date', 'asc')
            ->get();

        $totalExpenses = (int) $expenses->sum('amount');

        $cashOut = $totalPurchases + $totalExpenses;
        $netCashFlow = $cashIn - $cashOut;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'cash_in' => $cashIn,
            'cash_in_by_method' => $cashInByMethod,
            'payments' => $payments,
            'cash_out' => $cashOut,
            'total_purchases' => $totalPurchases,
            'purchases' => $purchases,
            'total_expenses' => $totalExpenses,
            'expenses' => $expenses,
            'net_cash_flow' => $netCashFlow,
            'simplification_notes' => [
                'br_r5' => 'Pembelian supplier dianggap tunai di tanggal pembelian; hutang supplier di luar scope sistem.',
            ],
        ];
    }

    /**
     * BR-R6, BR-R7: Piutang & Aging.
     * - Piutang per customer = Σ (total − pembayaran valid) untuk invoice non-void dengan sisa > 0, per tanggal laporan (BR-R6).
     * - Aging berdasarkan hari_lewat = tanggal_laporan − due_date (BR-R7):
     *   belum jatuh tempo (≤0), 1–30, 31–60, 61–90, >90 hari.
     */
    public function getReceivablesReport(?string $asOfDate = null): array
    {
        $reportDate = $asOfDate ? Carbon::parse($asOfDate)->startOfDay() : Carbon::today();

        // Ambil semua invoice non-void yang terbit sebelum atau pada tanggal laporan
        $invoices = Invoice::with(['customer', 'validPayments'])
            ->where('status', '!=', 'void')
            ->whereDate('issue_date', '<=', $reportDate->toDateString())
            ->get();

        $unpaidInvoices = collect();
        $totalReceivables = 0;

        $aging = [
            'not_due' => 0,     // <= 0 hari
            'days_1_30' => 0,   // 1 - 30 hari
            'days_31_60' => 0,  // 31 - 60 hari
            'days_61_90' => 0,  // 61 - 90 hari
            'days_over_90' => 0 // > 90 hari
        ];

        foreach ($invoices as $inv) {
            // Hitung pembayaran valid yang diterima hingga tanggal laporan
            $paidUpToDate = (int) $inv->validPayments
                ->filter(fn (Payment $p) => $p->paid_at->startOfDay()->lte($reportDate))
                ->sum('amount');

            $remaining = max(0, (int) $inv->total - $paidUpToDate);

            if ($remaining > 0) {
                // BR-R7: hari_lewat = tanggal_laporan - due_date
                // Jika due_date di masa lalu (misal 40 hari lalu), diffInDays bernilai positif 40
                $daysOverdue = (int) $inv->due_date->startOfDay()->diffInDays($reportDate, false);

                if ($daysOverdue <= 0) {
                    $aging['not_due'] += $remaining;
                    $bucket = 'not_due';
                } elseif ($daysOverdue <= 30) {
                    $aging['days_1_30'] += $remaining;
                    $bucket = 'days_1_30';
                } elseif ($daysOverdue <= 60) {
                    $aging['days_31_60'] += $remaining;
                    $bucket = 'days_31_60';
                } elseif ($daysOverdue <= 90) {
                    $aging['days_61_90'] += $remaining;
                    $bucket = 'days_61_90';
                } else {
                    $aging['days_over_90'] += $remaining;
                    $bucket = 'days_over_90';
                }

                $totalReceivables += $remaining;

                $inv->setAttribute('calculated_remaining', $remaining);
                $inv->setAttribute('calculated_days_overdue', $daysOverdue);
                $inv->setAttribute('calculated_bucket', $bucket);
                $unpaidInvoices->push($inv);
            }
        }

        // BR-R6: Rekap per customer
        $customerSummary = Customer::all()->map(function (Customer $cust) use ($unpaidInvoices) {
            $custInvoices = $unpaidInvoices->where('customer_id', $cust->id);
            $total = (int) $custInvoices->sum('calculated_remaining');

            return [
                'customer' => $cust,
                'total_receivable' => $total,
                'invoice_count' => $custInvoices->count(),
            ];
        })
        ->filter(fn ($row) => $row['total_receivable'] > 0)
        ->sortByDesc('total_receivable')
        ->values();

        return [
            'as_of_date' => $reportDate->toDateString(),
            'total_receivables' => $totalReceivables,
            'aging' => $aging,
            'customer_summary' => $customerSummary,
            'unpaid_invoices' => $unpaidInvoices->sortBy('due_date')->values(),
        ];
    }

    /**
     * BR-R8, PRD Modul 10: Ringkasan Dashboard.
     * Total piutang di dashboard = total di laporan piutang = Σ piutang per customer.
     */
    public function getDashboardSummary(): array
    {
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth()->toDateString();
        $endOfMonth = $today->copy()->endOfMonth()->toDateString();

        // 1. Omzet bulan ini (BR-R1)
        $revenueData = $this->getRevenueReport($startOfMonth, $endOfMonth);
        $monthRevenue = $revenueData['total_revenue'];

        // 2. Piutang berjalan dan jatuh tempo (BR-R6, BR-R7, BR-R8)
        $receivablesData = $this->getReceivablesReport($today->toDateString());
        $totalReceivables = $receivablesData['total_receivables'];
        $overdueReceivables = $receivablesData['aging']['days_1_30']
            + $receivablesData['aging']['days_31_60']
            + $receivablesData['aging']['days_61_90']
            + $receivablesData['aging']['days_over_90'];
        $notDueReceivables = $receivablesData['aging']['not_due'];

        // 3. Stok menipis (qty_on_hand <= min_stock)
        $lowStockParts = Part::where('is_active', true)
            ->whereColumn('qty_on_hand', '<=', 'min_stock')
            ->orderBy('qty_on_hand', 'asc')
            ->take(10)
            ->get();
        $lowStockCount = Part::where('is_active', true)
            ->whereColumn('qty_on_hand', '<=', 'min_stock')
            ->count();

        // 4. Lima order servis terbaru
        $recentWorkOrders = WorkOrder::with(['customer', 'equipmentUnit', 'mechanic'])
            ->latest('id')
            ->take(5)
            ->get();

        // 5. Arus kas bulan ini
        $cashFlowData = $this->getCashFlowReport($startOfMonth, $endOfMonth);

        return [
            'month_revenue' => $monthRevenue,
            'total_receivables' => $totalReceivables,
            'overdue_receivables' => $overdueReceivables,
            'not_due_receivables' => $notDueReceivables,
            'low_stock_parts' => $lowStockParts,
            'low_stock_count' => $lowStockCount,
            'recent_work_orders' => $recentWorkOrders,
            'cash_flow' => $cashFlowData,
            'receivables_aging' => $receivablesData['aging'],
        ];
    }
}
