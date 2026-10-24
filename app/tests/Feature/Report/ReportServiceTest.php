<?php

namespace Tests\Feature\Report;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportService $reportService;
    protected User $admin;
    protected Customer $customerA;
    protected Customer $customerB;
    protected Customer $customerC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportService = app(ReportService::class);
        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->customerA = Customer::create([
            'code' => 'CUST-A',
            'name' => 'PT Alfa Mandiri',
            'type' => 'company',
            'payment_term_days' => 30,
        ]);

        $this->customerB = Customer::create([
            'code' => 'CUST-B',
            'name' => 'CV Bravo Sentosa',
            'type' => 'company',
            'payment_term_days' => 14,
        ]);

        $this->customerC = Customer::create([
            'code' => 'CUST-C',
            'name' => 'Bapak Charlie',
            'type' => 'individual',
            'payment_term_days' => 0,
        ]);
    }

    /**
     * T-07 (BR-R7):
     * Invoice jatuh tempo 40 hari lalu, sisa 500.000 -> masuk bucket 31–60.
     * Nilai harapan dihitung secara independen dari kode produksi.
     */
    public function test_t07_br_r7_receivables_aging_bucket_31_to_60(): void
    {
        $today = Carbon::parse('2026-10-20');
        Carbon::setTestNow($today);

        // Due date = 40 hari lalu = 2026-09-10
        $dueDate = $today->copy()->subDays(40);
        $issueDate = $dueDate->copy()->subDays(30);

        // Buat invoice dengan sisa 500.000 (total 500.000, bayar 0)
        Invoice::create([
            'invoice_number' => 'INV-T07-0001',
            'customer_id' => $this->customerA->id,
            'source_type' => 'counter_sale',
            'issue_date' => $issueDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => 500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 500000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        $report = $this->reportService->getReceivablesReport('2026-10-20');

        // Nilai ekspektasi independen:
        $expectedAgingDays31To60 = 500000;
        $expectedAgingNotDue = 0;
        $expectedAgingDays1To30 = 0;
        $expectedAgingDays61To90 = 0;
        $expectedAgingDaysOver90 = 0;
        $expectedTotalReceivables = 500000;

        $this->assertEquals($expectedAgingDays31To60, $report['aging']['days_31_60'], 'Invoice 40 hari lalu wajib masuk bucket 31-60 (T-07).');
        $this->assertEquals($expectedAgingNotDue, $report['aging']['not_due']);
        $this->assertEquals($expectedAgingDays1To30, $report['aging']['days_1_30']);
        $this->assertEquals($expectedAgingDays61To90, $report['aging']['days_61_90']);
        $this->assertEquals($expectedAgingDaysOver90, $report['aging']['days_over_90']);
        $this->assertEquals($expectedTotalReceivables, $report['total_receivables']);

        Carbon::setTestNow();
    }

    /**
     * T-10 (BR-R8):
     * Total piutang di dashboard = total di laporan piutang = Σ piutang per customer.
     * Nilai harapan dihitung secara independen dari kode produksi.
     */
    public function test_t10_br_r8_consistency_dashboard_reports_and_customer_receivables(): void
    {
        $today = Carbon::parse('2026-10-20');
        Carbon::setTestNow($today);

        // 1. Customer A:
        // Invoice A1: total 1.000.000, dibayar 400.000 -> sisa independen = 600.000
        $invA1 = Invoice::create([
            'invoice_number' => 'INV-R8-A1',
            'customer_id' => $this->customerA->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'subtotal' => 1000000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 1000000,
            'status' => 'partially_paid',
            'created_by' => $this->admin->id,
        ]);
        Payment::create([
            'invoice_id' => $invA1->id,
            'paid_at' => '2026-10-05',
            'amount' => 400000,
            'method' => 'transfer',
            'status' => 'valid',
            'created_by' => $this->admin->id,
        ]);

        // Invoice A2: total 500.000, dibayar 500.000 (lunas) -> sisa independen = 0
        $invA2 = Invoice::create([
            'invoice_number' => 'INV-R8-A2',
            'customer_id' => $this->customerA->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-02',
            'due_date' => '2026-10-16',
            'subtotal' => 500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 500000,
            'status' => 'paid',
            'created_by' => $this->admin->id,
        ]);
        Payment::create([
            'invoice_id' => $invA2->id,
            'paid_at' => '2026-10-04',
            'amount' => 500000,
            'method' => 'cash',
            'status' => 'valid',
            'created_by' => $this->admin->id,
        ]);

        // 2. Customer B:
        // Invoice B1: total 750.000, belum bayar -> sisa independen = 750.000
        Invoice::create([
            'invoice_number' => 'INV-R8-B1',
            'customer_id' => $this->customerB->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-10',
            'due_date' => '2026-10-24',
            'subtotal' => 750000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 750000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        // Invoice B2 (VOID): total 300.000 -> sisa independen = 0 (void tidak boleh dihitung piutang)
        Invoice::create([
            'invoice_number' => 'INV-R8-B2',
            'customer_id' => $this->customerB->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-12',
            'due_date' => '2026-10-26',
            'subtotal' => 300000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 300000,
            'status' => 'void',
            'void_reason' => 'Salah input',
            'created_by' => $this->admin->id,
        ]);

        // 3. Customer C:
        // Invoice C1: total 250.000, dibayar 100.000 -> sisa independen = 150.000
        $invC1 = Invoice::create([
            'invoice_number' => 'INV-R8-C1',
            'customer_id' => $this->customerC->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-14',
            'due_date' => '2026-10-14',
            'subtotal' => 250000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 250000,
            'status' => 'partially_paid',
            'created_by' => $this->admin->id,
        ]);
        Payment::create([
            'invoice_id' => $invC1->id,
            'paid_at' => '2026-10-15',
            'amount' => 100000,
            'method' => 'cash',
            'status' => 'valid',
            'created_by' => $this->admin->id,
        ]);

        // Hitungan Ekspektasi Independen:
        // Customer A = (1.000.000 - 400.000) + (500.000 - 500.000) = 600.000
        // Customer B = 750.000 (invoice void diabaikan)
        // Customer C = 250.000 - 100.000 = 150.000
        // Total Seluruh Piutang Independen = 600.000 + 750.000 + 150.000 = 1.500.000
        $expectedReceivableCustA = 600000;
        $expectedReceivableCustB = 750000;
        $expectedReceivableCustC = 150000;
        $expectedTotalReceivables = 1500000;

        // 1. Verifikasi dari Model Customer
        $this->assertEquals($expectedReceivableCustA, $this->customerA->calculateTotalReceivables(), 'Piutang Customer A harus 600.000');
        $this->assertEquals($expectedReceivableCustB, $this->customerB->calculateTotalReceivables(), 'Piutang Customer B harus 750.000');
        $this->assertEquals($expectedReceivableCustC, $this->customerC->calculateTotalReceivables(), 'Piutang Customer C harus 150.000');

        $sumCustomerReceivables = $this->customerA->calculateTotalReceivables()
            + $this->customerB->calculateTotalReceivables()
            + $this->customerC->calculateTotalReceivables();
        $this->assertEquals($expectedTotalReceivables, $sumCustomerReceivables, 'Jumlah Σ piutang per customer harus 1.500.000');

        // 2. Verifikasi dari ReportService (Laporan Piutang)
        $reportData = $this->reportService->getReceivablesReport('2026-10-20');
        $this->assertEquals($expectedTotalReceivables, $reportData['total_receivables'], 'Total di laporan piutang harus 1.500.000');

        // 3. Verifikasi dari Dashboard Summary
        $dashboardData = $this->reportService->getDashboardSummary();
        $this->assertEquals($expectedTotalReceivables, $dashboardData['total_receivables'], 'Total piutang di dashboard harus 1.500.000');

        // 4. BR-R8: Harus IDENTIK antara Dashboard, Laporan Piutang, dan Σ Customer
        $this->assertSame(
            $dashboardData['total_receivables'],
            $reportData['total_receivables'],
            'BR-R8: Total piutang di dashboard harus identik dengan total di laporan piutang.'
        );
        $this->assertSame(
            $reportData['total_receivables'],
            $sumCustomerReceivables,
            'BR-R8: Total di laporan piutang harus identik dengan Σ piutang per customer.'
        );

        Carbon::setTestNow();
    }

    /**
     * BR-R1: Omzet = Σ invoices.total dalam periode (inklusif).
     * Invoice void dikecualikan.
     * Nilai harapan dihitung secara independen dari kode produksi.
     */
    public function test_br_r1_revenue_report_inclusive_range_and_void_excluded(): void
    {
        // 1. Invoice dalam periode (5 Okt 2026): Total 300.000
        Invoice::create([
            'invoice_number' => 'INV-R1-01',
            'customer_id' => $this->customerA->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-05',
            'due_date' => '2026-10-20',
            'subtotal' => 300000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 300000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        // 2. Invoice dalam periode (15 Okt 2026): Subtotal 500.000, Diskon 50.000 -> Total 450.000
        Invoice::create([
            'invoice_number' => 'INV-R1-02',
            'customer_id' => $this->customerB->id,
            'source_type' => 'work_order',
            'issue_date' => '2026-10-15',
            'due_date' => '2026-10-29',
            'subtotal' => 500000,
            'discount_amount' => 50000,
            'tax_amount' => 0,
            'total' => 450000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        // 3. Invoice VOID dalam periode (20 Okt 2026): Total 200.000 -> DIKECUALIKAN
        Invoice::create([
            'invoice_number' => 'INV-R1-03',
            'customer_id' => $this->customerA->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-20',
            'due_date' => '2026-11-05',
            'subtotal' => 200000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 200000,
            'status' => 'void',
            'void_reason' => 'Batal',
            'created_by' => $this->admin->id,
        ]);

        // 4. Invoice di luar periode (1 Nov 2026): Total 500.000 -> DIKECUALIKAN
        Invoice::create([
            'invoice_number' => 'INV-R1-04',
            'customer_id' => $this->customerC->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'subtotal' => 500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 500000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        $report = $this->reportService->getRevenueReport('2026-10-01', '2026-10-31');

        // Nilai ekspektasi independen:
        // Omzet = 300.000 + 450.000 = 750.000
        $expectedTotalRevenue = 750000;
        $expectedInvoiceCount = 2;
        $expectedSubtotal = 800000; // 300.000 + 500.000
        $expectedDiscount = 50000;

        $this->assertEquals($expectedTotalRevenue, $report['total_revenue']);
        $this->assertEquals($expectedInvoiceCount, $report['invoice_count']);
        $this->assertEquals($expectedSubtotal, $report['subtotal']);
        $this->assertEquals($expectedDiscount, $report['discount_total']);
    }

    /**
     * BR-R2, BR-R3: Laba Rugi Sederhana.
     * - HPP hanya dihitung dari part (unit_cost_snapshot * qty). Jasa dianggap biaya 0.
     * - Laba kotor = Omzet - HPP.
     * - Laba bersih = Laba kotor - Beban operasional.
     * Nilai harapan dihitung secara independen dari kode produksi.
     */
    public function test_br_r2_and_br_r3_profit_and_loss_calculation(): void
    {
        $part = Part::create([
            'sku' => 'PRT-PL-01',
            'name' => 'Bearing Roda',
            'category' => 'Bearing',
            'unit' => 'Pcs',
            'sell_price' => 150000,
            'avg_cost' => 100000,
            'qty_on_hand' => 10,
            'min_stock' => 2,
            'is_active' => true,
        ]);

        $service = Service::create([
            'code' => 'SRV-PL-01',
            'name' => 'Jasa Pasang Bearing',
            'category' => 'Service',
            'default_price' => 200000,
            'is_active' => true,
        ]);

        // Buat Invoice:
        // - Item Part: 2 pcs @ 150.000 = 300.000 (snapshot HPP 100.000 * 2 = 200.000)
        // - Item Jasa: 1 pcs @ 200.000 = 200.000 (HPP = 0)
        // Total Invoice = 500.000
        $invoice = Invoice::create([
            'invoice_number' => 'INV-PL-001',
            'customer_id' => $this->customerA->id,
            'source_type' => 'work_order',
            'issue_date' => '2026-10-10',
            'due_date' => '2026-10-25',
            'subtotal' => 500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 500000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_type' => 'part',
            'part_id' => $part->id,
            'description' => 'Bearing Roda',
            'qty' => 2,
            'unit_price' => 150000,
            'unit_cost_snapshot' => 100000,
            'line_total' => 300000,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_type' => 'service',
            'service_id' => $service->id,
            'description' => 'Jasa Pasang Bearing',
            'qty' => 1,
            'unit_price' => 200000,
            'unit_cost_snapshot' => 0, // BR-R2: Jasa HPP = 0
            'line_total' => 200000,
        ]);

        // Beban Operasional: 80.000
        Expense::create([
            'expense_date' => '2026-10-15',
            'category' => 'Listrik & Air',
            'amount' => 80000,
            'description' => 'Tagihan PLN bengkel',
            'created_by' => $this->admin->id,
        ]);

        // Pengeluaran di luar periode: 50.000 -> DIKECUALIKAN
        Expense::create([
            'expense_date' => '2026-11-05',
            'category' => 'Konsumsi',
            'amount' => 50000,
            'description' => 'Kopi mekanik November',
            'created_by' => $this->admin->id,
        ]);

        $report = $this->reportService->getProfitLossReport('2026-10-01', '2026-10-31');

        // Nilai ekspektasi independen:
        // Omzet = 500.000
        // HPP = 2 * 100.000 = 200.000 (BR-R2)
        // Laba Kotor = 500.000 - 200.000 = 300.000 (BR-R3)
        // Beban Operasional = 80.000
        // Laba Bersih = 300.000 - 80.000 = 220.000 (BR-R3)
        $expectedRevenue = 500000;
        $expectedHpp = 200000;
        $expectedGrossProfit = 300000;
        $expectedExpenses = 80000;
        $expectedNetProfit = 220000;

        $this->assertEquals($expectedRevenue, $report['total_revenue']);
        $this->assertEquals($expectedHpp, $report['total_hpp'], 'HPP harus berasal dari item part saja (BR-R2).');
        $this->assertEquals($expectedGrossProfit, $report['gross_profit'], 'Laba kotor = Omzet - HPP (BR-R3).');
        $this->assertEquals($expectedExpenses, $report['total_expenses']);
        $this->assertEquals($expectedNetProfit, $report['net_profit'], 'Laba bersih = Laba kotor - Beban operasional (BR-R3).');
    }

    /**
     * BR-R4, BR-R5: Arus Kas.
     * - Kas masuk = Σ pembayaran valid dalam periode (pembayaran void dikecualikan).
     * - Kas keluar = Σ pembelian supplier (tunai) + Σ beban operasional.
     * - Kas bersih = Kas masuk - Kas keluar.
     * Nilai harapan dihitung secara independen dari kode produksi.
     */
    public function test_br_r4_and_br_r5_cash_flow_calculation(): void
    {
        $inv = Invoice::create([
            'invoice_number' => 'INV-CF-01',
            'customer_id' => $this->customerA->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'subtotal' => 1000000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 1000000,
            'status' => 'partially_paid',
            'created_by' => $this->admin->id,
        ]);

        // Pembayaran 1 (valid, masuk periode): 300.000
        Payment::create([
            'invoice_id' => $inv->id,
            'paid_at' => '2026-10-05',
            'amount' => 300000,
            'method' => 'cash',
            'status' => 'valid',
            'created_by' => $this->admin->id,
        ]);

        // Pembayaran 2 (valid, masuk periode): 150.000
        Payment::create([
            'invoice_id' => $inv->id,
            'paid_at' => '2026-10-12',
            'amount' => 150000,
            'method' => 'transfer',
            'status' => 'valid',
            'created_by' => $this->admin->id,
        ]);

        // Pembayaran 3 (VOID, masuk periode): 200.000 -> DIKECUALIKAN
        Payment::create([
            'invoice_id' => $inv->id,
            'paid_at' => '2026-10-15',
            'amount' => 200000,
            'method' => 'transfer',
            'status' => 'void',
            'void_reason' => 'Transfer gagal',
            'created_by' => $this->admin->id,
        ]);

        // Pembelian barang masuk dari supplier (BR-R5, dianggap tunai): 250.000
        $supplier = Supplier::create([
            'code' => 'SUP-01',
            'name' => 'PT Sparepart Jaya',
            'phone' => '0811111111',
        ]);
        Purchase::create([
            'purchase_number' => 'PO-2610-0001',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-10-08',
            'total' => 250000,
            'notes' => 'Beli oli mesin',
            'created_by' => $this->admin->id,
        ]);

        // Beban operasional dalam periode: 75.000
        Expense::create([
            'expense_date' => '2026-10-14',
            'category' => 'Konsumsi',
            'amount' => 75000,
            'description' => 'Makan siang kru lembur',
            'created_by' => $this->admin->id,
        ]);

        $report = $this->reportService->getCashFlowReport('2026-10-01', '2026-10-31');

        // Nilai ekspektasi independen:
        // Kas Masuk = 300.000 + 150.000 = 450.000 (BR-R4)
        // Kas Keluar = 250.000 (pembelian) + 75.000 (beban operasional) = 325.000 (BR-R5)
        // Kas Bersih = 450.000 - 325.000 = 125.000
        $expectedCashIn = 450000;
        $expectedCashOut = 325000;
        $expectedNetCashFlow = 125000;

        $this->assertEquals($expectedCashIn, $report['cash_in'], 'Kas masuk harus 450.000 (BR-R4).');
        $this->assertEquals($expectedCashOut, $report['cash_out'], 'Kas keluar harus 325.000 (BR-R5).');
        $this->assertEquals($expectedNetCashFlow, $report['net_cash_flow'], 'Kas bersih = Kas masuk - Kas keluar.');
    }

    /**
     * BR-R7: Klasifikasi lengkap seluruh bucket umur piutang (aging).
     * Nilai harapan dihitung secara independen dari kode produksi.
     */
    public function test_br_r7_aging_all_buckets_classification(): void
    {
        $today = Carbon::parse('2026-10-20');
        Carbon::setTestNow($today);

        // 1. Belum jatuh tempo (hari_lewat <= 0): due date hari ini (2026-10-20), sisa 100.000
        Invoice::create([
            'invoice_number' => 'INV-AG-01',
            'customer_id' => $this->customerA->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-10-10',
            'due_date' => '2026-10-20',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 100000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        // 2. 1 - 30 hari: due date 15 hari lalu (2026-10-05), sisa 200.000
        Invoice::create([
            'invoice_number' => 'INV-AG-02',
            'customer_id' => $this->customerA->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-09-20',
            'due_date' => '2026-10-05',
            'subtotal' => 200000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 200000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        // 3. 31 - 60 hari: due date 45 hari lalu (2026-09-05), sisa 300.000
        Invoice::create([
            'invoice_number' => 'INV-AG-03',
            'customer_id' => $this->customerB->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-08-20',
            'due_date' => '2026-09-05',
            'subtotal' => 300000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 300000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        // 4. 61 - 90 hari: due date 75 hari lalu (2026-08-06), sisa 400.000
        Invoice::create([
            'invoice_number' => 'INV-AG-04',
            'customer_id' => $this->customerB->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-07-20',
            'due_date' => '2026-08-06',
            'subtotal' => 400000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 400000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        // 5. > 90 hari: due date 120 hari lalu (2026-06-22), sisa 500.000
        Invoice::create([
            'invoice_number' => 'INV-AG-05',
            'customer_id' => $this->customerC->id,
            'source_type' => 'counter_sale',
            'issue_date' => '2026-06-01',
            'due_date' => '2026-06-22',
            'subtotal' => 500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 500000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        $report = $this->reportService->getReceivablesReport('2026-10-20');

        // Nilai ekspektasi independen:
        $expectedNotDue = 100000;
        $expected1To30 = 200000;
        $expected31To60 = 300000;
        $expected61To90 = 400000;
        $expectedOver90 = 500000;
        $expectedTotal = 1500000;

        $this->assertEquals($expectedNotDue, $report['aging']['not_due']);
        $this->assertEquals($expected1To30, $report['aging']['days_1_30']);
        $this->assertEquals($expected31To60, $report['aging']['days_31_60']);
        $this->assertEquals($expected61To90, $report['aging']['days_61_90']);
        $this->assertEquals($expectedOver90, $report['aging']['days_over_90']);
        $this->assertEquals($expectedTotal, $report['total_receivables']);

        Carbon::setTestNow();
    }
}
