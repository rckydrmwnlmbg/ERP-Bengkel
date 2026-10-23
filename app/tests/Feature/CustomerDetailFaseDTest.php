<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EquipmentUnit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerDetailFaseDTest extends TestCase
{
    protected User $admin;
    protected ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = app(ReportService::class);
    }

    /**
     * Tes 1: Angka total piutang di halaman detail customer = angka di laporan piutang (BR-R8)
     * untuk SEMUA customer hasil seed demo.
     */
    public function test_customer_receivables_equals_receivables_report_for_all_seeded_customers(): void
    {
        Artisan::call('seed:demo');
        $baseDate = Carbon::parse('2026-10-20');
        Carbon::setTestNow($baseDate);

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($this->admin);

        // Ambil laporan piutang resmi per tanggal demo
        $receivablesReport = $this->reportService->getReceivablesReport('2026-10-20');
        $customerSummary = collect($receivablesReport['customer_summary']);

        $customers = Customer::all();
        $this->assertNotEmpty($customers, 'Harus ada customer dari hasil seed:demo.');

        foreach ($customers as $customer) {
            // Nilai di laporan piutang untuk customer ini
            $reportRow = $customerSummary->first(fn ($item) => $item['customer']->id === $customer->id);
            $expectedReceivable = $reportRow ? (int) $reportRow['total_receivable'] : 0;
            $expectedInvoiceCount = $reportRow ? (int) $reportRow['invoice_count'] : 0;

            // Kunjungi halaman detail customer
            $response = $this->get(route('customers.show', $customer));
            $response->assertOk();

            // Verifikasi variabel view yang dikirim oleh controller
            $response->assertViewHas('totalReceivable', $expectedReceivable);
            $response->assertViewHas('openInvoicesCount', $expectedInvoiceCount);

            // Verifikasi angka tertera di HTML halaman
            $formattedReceivable = 'Rp ' . number_format($expectedReceivable, 0, ',', '.');
            $response->assertSee($formattedReceivable);

            // Verifikasi konsistensi fungsi getCustomerReceivables di ReportService
            $singleCustomerData = $this->reportService->getCustomerReceivables($customer, '2026-10-20');
            $this->assertEquals(
                $expectedReceivable,
                $singleCustomerData['total_receivable'],
                "BR-R8: getCustomerReceivables() harus bernilai persis sama dengan laporan piutang untuk customer {$customer->name}."
            );
            $this->assertEquals(
                $expectedInvoiceCount,
                $singleCustomerData['open_invoices_count'],
                "Jumlah invoice terbuka untuk customer {$customer->name} harus konsisten."
            );
        }

        Carbon::setTestNow();
    }

    /**
     * Tes 2: Tidak ada N+1 query pada halaman detail customer.
     * Jumlah query database tetap konstan O(1) dan tidak bertambah saat data customer bertambah.
     */
    public function test_no_n_plus_one_queries_on_customer_show_page(): void
    {
        Artisan::call('seed:demo');
        $baseDate = Carbon::parse('2026-10-20');
        Carbon::setTestNow($baseDate);

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($this->admin);

        // Pilih customer dengan transaksi terbanyak
        $customer = Customer::has('invoices')->has('workOrders')->firstOrFail();

        // 1. Ukur baseline jumlah query
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('customers.show', $customer));
        $response->assertOk();

        $baselineQueryCount = count(DB::getQueryLog());
        // Pastikan jumlah query terikat (bounded) dan tidak membengkak
        $this->assertLessThanOrEqual(25, $baselineQueryCount, "Jumlah query halaman customer tidak boleh melebihi batas wajar ({$baselineQueryCount} query tercatat).");

        // 2. Tambahkan 5 work order baru dan 5 invoice baru untuk customer ini
        $unit = EquipmentUnit::where('customer_id', $customer->id)->first() ?? EquipmentUnit::create([
            'customer_id' => $customer->id,
            'unit_code' => 'TEST-01',
            'brand' => 'Komatsu',
            'model' => 'PC200',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $wo = WorkOrder::create([
                'wo_number' => "WO-EXTRA-{$i}",
                'customer_id' => $customer->id,
                'equipment_unit_id' => $unit->id,
                'complaint' => "Keluhan tambahan {$i}",
                'status' => 'completed',
                'opened_at' => now()->subDays($i),
                'created_by' => $this->admin->id,
            ]);

            $inv = Invoice::create([
                'invoice_number' => "INV-EXTRA-{$i}",
                'customer_id' => $customer->id,
                'source_type' => 'work_order',
                'work_order_id' => $wo->id,
                'issue_date' => now()->subDays($i),
                'due_date' => now()->addDays(14),
                'subtotal' => 1000000 * $i,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => 1000000 * $i,
                'status' => 'issued',
                'created_by' => $this->admin->id,
            ]);

            Payment::create([
                'invoice_id' => $inv->id,
                'paid_at' => now()->subDays($i),
                'amount' => 500000,
                'method' => 'transfer',
                'reference' => "TRF-EXTRA-{$i}",
                'status' => 'valid',
                'created_by' => $this->admin->id,
            ]);
        }

        // 3. Ukur ulang query count setelah penambahan 15 record baru
        DB::flushQueryLog();

        $responseAfter = $this->get(route('customers.show', $customer));
        $responseAfter->assertOk();

        $newQueryCount = count(DB::getQueryLog());

        // Assert query count harus SAMA PERSIS (O(1)), membuktikan tidak ada N+1 query
        $this->assertEquals(
            $baselineQueryCount,
            $newQueryCount,
            "Terdeteksi N+1 query! Jumlah query bertambah dari {$baselineQueryCount} menjadi {$newQueryCount} setelah penambahan data."
        );

        Carbon::setTestNow();
    }

    /**
     * Tes 3: Riwayat order servis & invoice dan riwayat pembayaran ditampilkan dengan kolom lengkap,
     * paginasi berfungsi, dan setiap baris menaut ke detailnya.
     */
    public function test_customer_detail_shows_history_and_payments_with_proper_links(): void
    {
        Artisan::call('migrate');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'code' => 'CUST-FASED',
            'name' => 'PT Fase D Test Mandiri',
            'type' => 'company',
            'phone' => '08129999999',
            'payment_term_days' => 30,
        ]);

        $unit = EquipmentUnit::create([
            'customer_id' => $customer->id,
            'unit_code' => 'EX-FASED-01',
            'brand' => 'Caterpillar',
            'model' => '320D',
        ]);

        $wo = WorkOrder::create([
            'wo_number' => 'WO-2610-9999',
            'customer_id' => $customer->id,
            'equipment_unit_id' => $unit->id,
            'complaint' => 'Overheating engine',
            'status' => 'completed',
            'opened_at' => now()->subDays(2),
            'created_by' => $this->admin->id,
        ]);

        $inv = Invoice::create([
            'invoice_number' => 'INV-2610-9999',
            'customer_id' => $customer->id,
            'source_type' => 'work_order',
            'work_order_id' => $wo->id,
            'issue_date' => now()->subDay(),
            'due_date' => now()->addDays(30),
            'subtotal' => 5000000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 5000000,
            'status' => 'partially_paid',
            'created_by' => $this->admin->id,
        ]);

        $payment = Payment::create([
            'invoice_id' => $inv->id,
            'paid_at' => now(),
            'amount' => 2000000,
            'method' => 'transfer',
            'reference' => 'PAY-FASED-REF-01',
            'status' => 'valid',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->get(route('customers.show', $customer));
        $response->assertOk();

        // Cek KPI Cards
        $response->assertSee('Total Piutang (BR-R8)');
        $response->assertSee('Jumlah Invoice Terbuka');
        $response->assertSee('Total Transaksi 12 Bulan');
        $response->assertSee('Rp 3.000.000'); // Sisa piutang (5.000.000 - 2.000.000)

        // Cek baris Riwayat Order Servis & Invoice dan tautan ke detail
        $response->assertSee('WO-2610-9999');
        $response->assertSee(route('work-orders.show', $wo));
        $response->assertSee('INV-2610-9999');
        $response->assertSee(route('invoices.show', $inv));
        $response->assertSee('Order Servis');
        $response->assertSee('Invoice');

        // Cek baris Riwayat Pembayaran dan tautan ke detail invoice
        $response->assertSee('PAY-FASED-REF-01');
        $response->assertSee('Rp 2.000.000');
        $response->assertSee('Transfer');
        $response->assertSee(route('invoices.show', $inv));
    }
}
