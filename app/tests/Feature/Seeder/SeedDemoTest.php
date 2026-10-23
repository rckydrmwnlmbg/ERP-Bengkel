<?php

namespace Tests\Feature\Seeder;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerServiceRate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SeedDemoTest extends TestCase
{
    /**
     * Uji eksekusi perintah seed:demo dan verifikasi seluruh invarian SEED_SPEC.md dan Fase E.
     */
    public function test_seed_demo_executes_successfully_and_satisfies_all_invariants(): void
    {
        // 1. Jalankan perintah seed:demo --fresh
        $exitCode = Artisan::call('seed:demo', ['--fresh' => true]);
        $this->assertEquals(0, $exitCode, 'Artisan seed:demo harus selesai dengan exit code 0.');

        $baseDate = Carbon::parse('2026-10-20 23:59:59');
        Carbon::setTestNow($baseDate);

        // 2. Invarian 1 (BR-S1): Tidak ada stok minus & qty_on_hand == Σ movements
        $parts = Part::all();
        $this->assertGreaterThanOrEqual(45, $parts->count(), 'Harus ada minimal 45 sparepart.');

        foreach ($parts as $part) {
            $this->assertGreaterThanOrEqual(0, $part->qty_on_hand, "Stok part {$part->sku} tidak boleh minus.");

            $movementsSum = (int) StockMovement::where('part_id', $part->id)->sum('qty_change');
            $this->assertEquals(
                $movementsSum,
                $part->qty_on_hand,
                "Invarian 1 (BR-S1): qty_on_hand pada {$part->sku} harus sama dengan Σ movements."
            );
        }

        // 3. Invarian 2: harga_jual > avg_cost untuk semua part aktif
        foreach ($parts as $part) {
            if ($part->is_active) {
                $this->assertGreaterThan(
                    $part->avg_cost,
                    $part->sell_price,
                    "Invarian 2: Harga jual {$part->sku} harus lebih besar dari avg_cost."
                );
            }
        }

        // 4. Invarian 3 & 4: Formula invoice, tidak ada total <= 0, valid payment <= total, tidak ada pembayaran invoice void
        $invoices = Invoice::with(['items', 'validPayments'])->get();
        $this->assertGreaterThanOrEqual(160, $invoices->count(), 'Harus ada minimal 160 invoice.');

        $voidInvoicesCount = 0;
        $discountedCount = 0;
        foreach ($invoices as $inv) {
            $expectedTotal = (int) $inv->subtotal - (int) $inv->discount_amount + (int) $inv->tax_amount;
            $this->assertEquals($expectedTotal, $inv->total, "Invarian 3: Formula invoice {$inv->invoice_number} harus konsisten.");
            $this->assertGreaterThan(0, $inv->total, "Invarian 4: Total invoice harus > 0.");

            $paid = (int) $inv->validPayments->sum('amount');
            $this->assertLessThanOrEqual($inv->total, $paid, "Invarian 3: Pembayaran tidak boleh melebihi total tagihan.");

            if ($inv->status === 'void') {
                $voidInvoicesCount++;
                $this->assertEquals(0, $inv->validPayments()->count(), "Invarian 4: Invoice void tidak boleh memiliki pembayaran valid.");
            }

            if ($inv->discount_amount > 0) {
                $discountedCount++;
            }
        }

        $this->assertEquals(3, $voidInvoicesCount, 'Variasi kasus: harus ada persis 3 invoice void (SEED_SPEC).');
        $this->assertGreaterThanOrEqual(3, $discountedCount, 'Variasi kasus: minimal 3 invoice memiliki diskon.');

        // 5. Invarian 5 (BR-R8): Konsistensi Total Piutang di Semua Tempat
        $reportService = app(ReportService::class);
        $receivablesReport = $reportService->getReceivablesReport('2026-10-20');
        $dashboardSummary = $reportService->getDashboardSummary();

        $sumCustomers = 0;
        foreach (Customer::all() as $cust) {
            $sumCustomers += $cust->calculateTotalReceivables();
        }

        $this->assertEquals(
            $receivablesReport['total_receivables'],
            $dashboardSummary['total_receivables'],
            'Invarian 5 (BR-R8): Total piutang di laporan harus identik dengan dashboard.'
        );

        $this->assertEquals(
            $receivablesReport['total_receivables'],
            $sumCustomers,
            'Invarian 5 (BR-R8): Total piutang di laporan harus identik dengan Σ customer.'
        );

        // 6. Invarian 6: Tanggal bayar >= tanggal invoice >= tanggal order
        $payments = Payment::with('invoice.workOrder')->where('status', 'valid')->get();
        $this->assertGreaterThanOrEqual(100, $payments->count());

        $installmentInvoices = [];
        foreach ($payments as $p) {
            $this->assertTrue(
                $p->paid_at->gte($p->invoice->issue_date),
                "Invarian 6: Tanggal bayar {$p->paid_at->toDateString()} harus >= tanggal invoice {$p->invoice->issue_date->toDateString()}"
            );

            if ($p->invoice->workOrder) {
                $this->assertTrue(
                    $p->invoice->issue_date->gte($p->invoice->workOrder->opened_at->toDateString()),
                    "Invarian 6: Tanggal invoice harus >= tanggal order servis dibuka."
                );
            }

            $installmentInvoices[$p->invoice_id] = ($installmentInvoices[$p->invoice_id] ?? 0) + 1;
        }

        $multiPaymentCount = count(array_filter($installmentInvoices, fn ($cnt) => $cnt >= 2));
        $this->assertGreaterThanOrEqual(5, $multiPaymentCount, 'Harus ada minimal 5 invoice dengan cicilan (2-3 kali bayar).');

        // 7. Variasi Kasus Wajib (SEED_SPEC)
        // a. Peringatan stok menipis: minimal 4 part <= min_stock, 1 part stok 0
        $lowStockCount = Part::where('is_active', true)->whereColumn('qty_on_hand', '<=', 'min_stock')->count();
        $zeroStockCount = Part::where('is_active', true)->where('qty_on_hand', 0)->count();
        $this->assertGreaterThanOrEqual(4, $lowStockCount, 'Harus ada minimal 4 part dengan stok <= min_stock.');
        $this->assertEquals(1, $zeroStockCount, 'Harus ada persis 1 part dengan stok 0 (SP-0020).');

        // b. Status order servis: 2 open, 2 in_progress, 3 completed, 2 cancelled
        $this->assertEquals(2, WorkOrder::where('status', 'open')->count(), 'Harus ada 2 WO status open.');
        $this->assertEquals(2, WorkOrder::where('status', 'in_progress')->count(), 'Harus ada 2 WO status in_progress.');
        $this->assertEquals(3, WorkOrder::where('status', 'completed')->count(), 'Harus ada 3 WO status completed.');
        $this->assertEquals(2, WorkOrder::where('status', 'cancelled')->count(), 'Harus ada 2 WO status cancelled.');

        // c. 3 Customer memiliki tarif khusus
        $customersWithSpecialRates = CustomerServiceRate::distinct('customer_id')->count('customer_id');
        $this->assertEquals(3, $customersWithSpecialRates, 'Harus ada 3 customer yang memiliki tarif jasa khusus.');

        // d. Distribusi Aging Piutang di semua 5 bucket > 0
        $this->assertGreaterThan(0, $receivablesReport['aging']['not_due'], 'Harus ada piutang pada bucket not_due.');
        $this->assertGreaterThan(0, $receivablesReport['aging']['days_1_30'], 'Harus ada piutang pada bucket days_1_30.');
        $this->assertGreaterThan(0, $receivablesReport['aging']['days_31_60'], 'Harus ada piutang pada bucket days_31_60.');
        $this->assertGreaterThan(0, $receivablesReport['aging']['days_61_90'], 'Harus ada piutang pada bucket days_61_90.');
        $this->assertGreaterThan(0, $receivablesReport['aging']['days_over_90'], 'Harus ada piutang pada bucket days_over_90.');

        // 8. Invarian Baru Fase E:
        // a. Akun Demo Tunggal (Sesuai README dan Halaman Depan)
        $expectedUsers = ['admin@bengkel.com', 'kasir@bengkel.com', 'mekanik1@bengkel.com', 'mekanik2@bengkel.com'];
        $actualUsers = User::where('is_active', true)->pluck('email')->toArray();
        sort($expectedUsers);
        sort($actualUsers);
        $this->assertEquals($expectedUsers, $actualUsers, 'Akun demo harus sesuai persis dengan README tanpa duplikat.');

        // b. Tidak ada timestamp di masa depan relatif waktu seeding
        $anchor = Carbon::parse('2026-10-20 23:59:59');
        $this->assertEquals(0, StockMovement::where('created_at', '>', $anchor)->count());
        $this->assertEquals(0, Invoice::where('created_at', '>', $anchor)->orWhere('issue_date', '>', '2026-10-20')->count());
        $this->assertEquals(0, Payment::where('created_at', '>', $anchor)->orWhere('paid_at', '>', '2026-10-20')->count());
        $this->assertEquals(0, WorkOrder::where('created_at', '>', $anchor)->orWhere('opened_at', '>', $anchor)->count());
        $this->assertEquals(0, Purchase::where('created_at', '>', $anchor)->orWhere('purchase_date', '>', '2026-10-20')->count());
        $this->assertEquals(0, Expense::where('created_at', '>', $anchor)->orWhere('expense_date', '>', '2026-10-20')->count());
        $this->assertEquals(0, AuditLog::where('created_at', '>', $anchor)->count());

        // c. Kartu Stok Urut Kronologis
        foreach ($parts as $part) {
            $movements = StockMovement::where('part_id', $part->id)->orderBy('id')->get();
            $prevTime = null;
            foreach ($movements as $m) {
                if ($prevTime) {
                    $this->assertTrue(
                        $m->created_at->gte($prevTime),
                        "Kartu stok part {$part->sku} harus kronologis: {$m->created_at} gte {$prevTime}"
                    );
                }
                $prevTime = $m->created_at;
            }
        }

        // d. Batasan Ukuran Transaksi Penjualan Toko
        $counterSales = Invoice::where('source_type', 'counter_sale')->with('items.part')->get();
        foreach ($counterSales as $cs) {
            $this->assertLessThanOrEqual(30000000, $cs->total, "Total satu invoice penjualan toko maksimal Rp 30.000.000 ({$cs->invoice_number})");
            foreach ($cs->items as $it) {
                $price = $it->part ? $it->part->sell_price : $it->unit_price;
                if ($price > 1000000) {
                    $this->assertLessThanOrEqual(10, $it->qty, "Qty item mahal > 1M maksimal 10 pada invoice {$cs->invoice_number}");
                } else {
                    $this->assertLessThanOrEqual(30, $it->qty, "Qty item murah maksimal 30 pada invoice {$cs->invoice_number}");
                }
            }
        }

        // e. Konsentrasi Piutang Maksimal 25% per Customer
        $totalRec = (int) $receivablesReport['total_receivables'];
        $this->assertGreaterThan(0, $totalRec);
        foreach (Customer::all() as $cust) {
            $cRec = (int) $cust->calculateTotalReceivables();
            $share = $cRec / $totalRec;
            $this->assertLessThanOrEqual(0.25, $share, "Customer {$cust->name} menguasai " . round($share * 100, 2) . "% piutang (maksimal 25%)");
        }

        // f. Omzet Bulanan Naik Bertahap dan Wajar (Rp 80M - 250M) untuk 12 Bulan
        for ($i = 11; $i >= 0; $i--) {
            $d = Carbon::parse('2026-10-20')->subMonths($i);
            $startStr = $d->copy()->startOfMonth()->toDateString();
            $endStr = ($i === 0) ? '2026-10-20' : $d->copy()->endOfMonth()->toDateString();
            $rev = (int) Invoice::where('status', '!=', 'void')
                ->whereDate('issue_date', '>=', $startStr)
                ->whereDate('issue_date', '<=', $endStr)
                ->sum('total');

            $monthStr = $d->format('Y-m');
            $this->assertGreaterThanOrEqual(80000000, $rev, "Omzet bulan {$monthStr} minimal Rp 80 juta");
            $this->assertLessThanOrEqual(250000000, $rev, "Omzet bulan {$monthStr} maksimal Rp 250 juta");
        }

        Carbon::setTestNow();
    }

    /**
     * Uji determinisme: seed:demo --fresh dijalankan dua kali menghasilkan metrik 100% identik.
     */
    public function test_seed_demo_is_deterministic_across_multiple_runs(): void
    {
        // Run 1
        Artisan::call('seed:demo', ['--fresh' => true]);
        $reportService = app(ReportService::class);
        $run1Invoices = Invoice::count();
        $run1PaidInvoices = Invoice::where('status', 'paid')->count();
        $run1VoidInvoices = Invoice::where('status', 'void')->count();
        $run1Payments = Payment::count();
        $run1StockMovements = StockMovement::count();
        $run1TotalReceivables = $reportService->getReceivablesReport('2026-10-20')['total_receivables'];
        $run1TotalRevenue = $reportService->getProfitLossReport('2025-11-01', '2026-10-31')['total_revenue'];

        // Run 2
        Artisan::call('seed:demo', ['--fresh' => true]);
        $run2Invoices = Invoice::count();
        $run2PaidInvoices = Invoice::where('status', 'paid')->count();
        $run2VoidInvoices = Invoice::where('status', 'void')->count();
        $run2Payments = Payment::count();
        $run2StockMovements = StockMovement::count();
        $run2TotalReceivables = $reportService->getReceivablesReport('2026-10-20')['total_receivables'];
        $run2TotalRevenue = $reportService->getProfitLossReport('2025-11-01', '2026-10-31')['total_revenue'];

        $this->assertEquals($run1Invoices, $run2Invoices, 'Jumlah invoice harus identik.');
        $this->assertEquals($run1PaidInvoices, $run2PaidInvoices, 'Jumlah invoice lunas harus identik.');
        $this->assertEquals($run1VoidInvoices, $run2VoidInvoices, 'Jumlah invoice void harus identik.');
        $this->assertEquals($run1Payments, $run2Payments, 'Jumlah pembayaran harus identik.');
        $this->assertEquals($run1StockMovements, $run2StockMovements, 'Jumlah mutasi stok harus identik.');
        $this->assertEquals($run1TotalReceivables, $run2TotalReceivables, 'Total piutang harus identik.');
        $this->assertEquals($run1TotalRevenue, $run2TotalRevenue, 'Total omzet harus identik.');
    }
}
