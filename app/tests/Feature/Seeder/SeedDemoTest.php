<?php

namespace Tests\Feature\Seeder;

use App\Models\Customer;
use App\Models\CustomerServiceRate;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\WorkOrder;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SeedDemoTest extends TestCase
{
    /**
     * Uji eksekusi perintah seed:demo dan verifikasi seluruh invarian SEED_SPEC.md.
     */
    public function test_seed_demo_executes_successfully_and_satisfies_all_invariants(): void
    {
        // 1. Jalankan perintah seed:demo
        $exitCode = Artisan::call('seed:demo');
        $this->assertEquals(0, $exitCode, 'Artisan seed:demo harus selesai dengan exit code 0.');

        $baseDate = Carbon::parse('2026-10-20');
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

        // 3. Invarian 2: harga_jual > avg_cost untuk semua part
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
        }

        $this->assertEquals(3, $voidInvoicesCount, 'Variasi kasus: harus ada persis 3 invoice void (SEED_SPEC).');

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
        }

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

        // d. Tunggakan besar > 90 hari
        $this->assertGreaterThan(0, $receivablesReport['aging']['days_over_90'], 'Harus ada piutang macet pada bucket > 90 hari.');

        Carbon::setTestNow();
    }
}
