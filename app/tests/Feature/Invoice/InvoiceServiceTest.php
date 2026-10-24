<?php

namespace Tests\Feature\Invoice;

use App\Exceptions\InvalidInvoiceOperationException;
use App\Exceptions\InvalidPaymentOperationException;
use App\Models\Customer;
use App\Models\CustomerServiceRate;
use App\Models\EquipmentUnit;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $invoiceService;
    protected PaymentService $paymentService;
    protected WorkOrderService $workOrderService;
    protected StockService $stockService;
    protected PricingService $pricingService;
    protected User $admin;
    protected User $cashier;
    protected Customer $customerMaju;
    protected EquipmentUnit $unitMaju;
    protected Part $filterOli;
    protected Service $gantiOli;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = app(InvoiceService::class);
        $this->paymentService = app(PaymentService::class);
        $this->workOrderService = app(WorkOrderService::class);
        $this->stockService = app(StockService::class);
        $this->pricingService = app(PricingService::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);

        // Pastikan setting pajak default = 0
        Setting::set('tax_rate_percent', 0);

        // Setup Customer "PT Maju"
        $this->customerMaju = Customer::create([
            'code' => 'CUST-MAJU',
            'name' => 'PT Maju',
            'type' => 'company',
            'phone' => '08123456789',
            'payment_term_days' => 30,
        ]);

        $this->unitMaju = EquipmentUnit::create([
            'customer_id' => $this->customerMaju->id,
            'brand' => 'Komatsu',
            'model' => 'PC200-8',
            'serial_number' => 'KMTPC200-998877',
            'unit_code' => 'EXC-01',
            'hour_meter' => 1200,
        ]);

        // Part "Filter Oli": stok 10, avg 100.000, sell 150.000 (T-03)
        $this->filterOli = Part::create([
            'sku' => 'FLT-OLI-01',
            'name' => 'Filter Oli',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 150000,
            'avg_cost' => 100000,
            'qty_on_hand' => 10,
            'min_stock' => 2,
            'is_active' => true,
        ]);

        StockMovement::create([
            'part_id' => $this->filterOli->id,
            'type' => 'PURCHASE_IN',
            'qty_change' => 10,
            'unit_cost_snapshot' => 100000,
            'balance_after' => 10,
            'reason' => 'Stok awal untuk pengujian T-03',
            'created_by' => $this->admin->id,
        ]);

        // Service "Ganti Oli": standard Rp200.000, tarif PT Maju Rp175.000 (T-03)
        $this->gantiOli = Service::create([
            'code' => 'SRV-GNT-OLI',
            'name' => 'Ganti Oli',
            'category' => 'Periodic Service',
            'default_price' => 200000,
            'is_active' => true,
        ]);

        CustomerServiceRate::create([
            'customer_id' => $this->customerMaju->id,
            'service_id' => $this->gantiOli->id,
            'price' => 175000,
        ]);
    }

    /**
     * T-03 (BR-P1, P2, I2, R1-R3)
     * Stok Filter Oli 10, avg Rp100.000, harga jual Rp150.000. Jasa "Ganti Oli" standar Rp200.000, tarif PT Maju Rp175.000.
     * Order PT Maju: 2 × Filter Oli + 1 × Ganti Oli.
     * - Item: 2×150.000 = 300.000; jasa 175.000 -> subtotal = total = 475.000 (diskon 0, pajak 0).
     * - Stok setelah order = 8. HPP = 2×100.000 = 200.000. Laba kotor = 275.000.
     * - Ubah tarif Ganti Oli PT Maju jadi 190.000 setelah invoice -> invoice tetap 475.000 (BR-P2).
     */
    public function test_t03_br_p1_p2_i2_r1_r3_work_order_invoice_exact_values(): Invoice
    {
        // 1. Buat order servis PT Maju
        $wo = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customerMaju->id,
            'equipment_unit_id' => $this->unitMaju->id,
            'complaint' => 'Servis berkala ganti oli dan filter',
        ], $this->admin->id);

        // Tambah 2 x Filter Oli (BR-P3, BR-S4)
        $this->workOrderService->addPartItem($wo, $this->filterOli->id, 2, null, $this->admin->id);

        // Tambah 1 x Ganti Oli (BR-P1: tarif khusus PT Maju Rp175.000)
        $this->workOrderService->addServiceItem($wo, $this->gantiOli->id, 1, $this->admin->id);

        // Cek stok setelah order = 8
        $this->filterOli->refresh();
        $this->assertEquals(8, $this->filterOli->qty_on_hand, 'Stok setelah order harus 8 (T-03).');

        // Selesaikan servis (transisi ke in_progress lalu completed)
        $this->workOrderService->transitionStatus($wo, 'in_progress', $this->admin->id);
        $this->workOrderService->transitionStatus($wo, 'completed', $this->admin->id);

        // 2. Terbitkan invoice dari order servis (BR-I4)
        $invoice = $this->invoiceService->createFromWorkOrder($wo, [
            'discount_amount' => 0,
        ], $this->cashier->id);

        // - Subtotal = total = 475.000 (diskon 0, pajak 0)
        $this->assertEquals(475000, $invoice->subtotal, 'Subtotal harus 475.000 (T-03).');
        $this->assertEquals(0, $invoice->discount_amount, 'Diskon harus 0 (T-03).');
        $this->assertEquals(0, $invoice->tax_amount, 'Pajak harus 0 (T-03).');
        $this->assertEquals(475000, $invoice->total, 'Total harus 475.000 (T-03).');

        // - HPP = 2 × 100.000 = 200.000 (BR-R2)
        $this->assertEquals(200000, $invoice->calculateHpp(), 'HPP harus 200.000 (T-03, BR-R2).');

        // - Laba kotor = 475.000 - 200.000 = 275.000 (BR-R3)
        $this->assertEquals(275000, $invoice->calculateGrossProfit(), 'Laba kotor harus 275.000 (T-03, BR-R3).');

        // - Status order servis menjadi invoiced
        $wo->refresh();
        $this->assertEquals('invoiced', $wo->status, 'Status order servis harus invoiced (BR-W1).');

        // 3. Ubah tarif Ganti Oli PT Maju jadi 190.000 setelah invoice -> invoice tetap 475.000 (BR-P2)
        CustomerServiceRate::where('customer_id', $this->customerMaju->id)
            ->where('service_id', $this->gantiOli->id)
            ->update(['price' => 190000]);

        $invoice->refresh();
        $this->assertEquals(475000, $invoice->total, 'Perubahan tarif jasa tidak boleh mengubah invoice yang sudah ada (BR-P2, T-03).');

        return $invoice;
    }

    /**
     * T-04 (BR-Y1, I8)
     * Lanjutan T-03:
     * - Bayar 300.000 -> partially_paid, sisa 175.000.
     * - Bayar 200.000 -> ditolak (melebihi sisa).
     * - Bayar 175.000 -> paid, sisa 0.
     */
    public function test_t04_br_y1_i8_payment_exact_values(): void
    {
        $invoice = $this->test_t03_br_p1_p2_i2_r1_r3_work_order_invoice_exact_values();

        // 1. Bayar 300.000 -> partially_paid, sisa 175.000
        $payment1 = $this->paymentService->recordPayment($invoice, [
            'amount' => 300000,
            'method' => 'transfer',
            'paid_at' => now()->toDateString(),
        ], $this->cashier->id);

        $invoice->refresh();
        $this->assertEquals('partially_paid', $invoice->status, 'Status harus partially_paid (T-04, BR-I8).');
        $this->assertEquals(175000, $invoice->remainingBalance(), 'Sisa tagihan harus 175.000 (T-04).');
        $this->assertEquals(300000, $invoice->totalPaid());

        // 2. Bayar 200.000 -> ditolak (melebihi sisa 175.000)
        try {
            $this->paymentService->recordPayment($invoice, [
                'amount' => 200000,
                'method' => 'cash',
            ], $this->cashier->id);
            $this->fail('Pembayaran 200.000 seharusnya ditolak karena melebihi sisa 175.000 (BR-Y1).');
        } catch (InvalidPaymentOperationException $e) {
            $this->assertStringContainsString('melebihi sisa tagihan', $e->getMessage());
        }

        // Pastikan sisa tagihan tetap 175.000
        $invoice->refresh();
        $this->assertEquals(175000, $invoice->remainingBalance(), 'Sisa tagihan tetap 175.000 setelah pembayaran ditolak.');

        // 3. Bayar 175.000 -> paid, sisa 0
        $payment2 = $this->paymentService->recordPayment($invoice, [
            'amount' => 175000,
            'method' => 'transfer',
            'paid_at' => now()->toDateString(),
        ], $this->cashier->id);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status, 'Status harus paid (T-04, BR-I8).');
        $this->assertEquals(0, $invoice->remainingBalance(), 'Sisa tagihan harus 0 (T-04).');
        $this->assertEquals(475000, $invoice->totalPaid());
    }

    /**
     * T-05 (BR-I6, Y2)
     * Void invoice yang punya pembayaran valid -> ditolak.
     * Void pembayaran 300.000 (admin, alasan) -> invoice kembali partially_paid / issued sesuai sisa.
     */
    public function test_t05_br_i6_y2_void_invoice_and_void_payment_exact_values(): void
    {
        $invoice = $this->test_t03_br_p1_p2_i2_r1_r3_work_order_invoice_exact_values();

        // Catat pembayaran 300.000
        $payment = $this->paymentService->recordPayment($invoice, [
            'amount' => 300000,
            'method' => 'transfer',
        ], $this->cashier->id);

        $invoice->refresh();
        $this->assertEquals('partially_paid', $invoice->status);

        // 1. Coba void invoice yang memiliki pembayaran valid -> DITOLAK (BR-I6)
        try {
            $this->invoiceService->voidInvoice($invoice, 'Koreksi salah tagihan', $this->admin->id);
            $this->fail('Void invoice dengan pembayaran valid harus ditolak (BR-I6, T-05).');
        } catch (InvalidInvoiceOperationException $e) {
            $this->assertStringContainsString('memiliki pembayaran yang valid', $e->getMessage());
        }

        // 2. Void pembayaran 300.000 (admin, wajib alasan) (BR-Y2)
        $this->paymentService->voidPayment($payment, 'Customer meminta refund transfer ganda', $this->admin->id);

        $payment->refresh();
        $this->assertEquals('void', $payment->status);
        $this->assertEquals('Customer meminta refund transfer ganda', $payment->void_reason);

        // Status invoice dihitung ulang menjadi 'issued' karena tidak ada lagi pembayaran valid (BR-Y2, BR-I8)
        $invoice->refresh();
        $this->assertEquals('issued', $invoice->status, 'Status invoice harus kembali issued setelah pembayaran divoid (T-05, BR-I8).');
        $this->assertEquals(475000, $invoice->remainingBalance());

        // 3. Setelah tidak ada pembayaran valid, void invoice sekarang berhasil (BR-I6)
        $this->invoiceService->voidInvoice($invoice, 'Order servis dibatalkan oleh pelanggan', $this->admin->id);

        $invoice->refresh();
        $this->assertEquals('void', $invoice->status);
        $this->assertEquals('Order servis dibatalkan oleh pelanggan', $invoice->void_reason);

        // Order servis kembali ke status completed (BR-I6)
        $wo = $invoice->workOrder;
        $wo->refresh();
        $this->assertEquals('completed', $wo->status, 'Order servis harus kembali ke status completed setelah invoice di-void (BR-I6).');
    }

    /**
     * T-06 (BR-S5)
     * Penjualan langsung 3 × Filter Oli @150.000 dengan diskon 20.000 -> total 430.000, stok −3, HPP 3×avg.
     * Void -> stok +3 (SALE_RETURN).
     */
    public function test_t06_br_s5_direct_sale_and_void_exact_values(): void
    {
        $stokAwal = $this->filterOli->qty_on_hand; // 10
        $avgCostAwal = $this->filterOli->avg_cost;  // 100.000

        // 1. Buat penjualan langsung 3 × Filter Oli @ 150.000 dengan diskon 20.000
        $invoice = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'issue_date' => now()->toDateString(),
            'discount_amount' => 20000,
            'items' => [
                [
                    'part_id' => $this->filterOli->id,
                    'qty' => 3,
                ],
            ],
        ], $this->cashier->id);

        // Cek total = 3 × 150.000 (450.000) - 20.000 = 430.000 (T-06)
        $this->assertEquals(450000, $invoice->subtotal);
        $this->assertEquals(20000, $invoice->discount_amount);
        $this->assertEquals(430000, $invoice->total, 'Total penjualan langsung harus 430.000 (T-06).');

        // Cek stok berkurang 3 (10 - 3 = 7) (BR-S5)
        $this->filterOli->refresh();
        $this->assertEquals(7, $this->filterOli->qty_on_hand, 'Stok harus berkurang 3 menjadi 7 (T-06, BR-S5).');

        // Cek HPP = 3 × avg (3 × 100.000 = 300.000)
        $this->assertEquals(300000, $invoice->calculateHpp(), 'HPP harus 3 x avg = 300.000 (T-06).');

        // Pastikan ada pergerakan SALE_OUT di stock_movements
        $movementOut = StockMovement::where('part_id', $this->filterOli->id)
            ->where('type', 'SALE_OUT')
            ->latest('id')
            ->first();
        $this->assertNotNull($movementOut);
        $this->assertEquals(-3, $movementOut->qty_change);
        $this->assertEquals(100000, $movementOut->unit_cost_snapshot);

        // 2. Void invoice penjualan langsung (BR-I6, BR-S5) -> stok +3 (SALE_RETURN)
        $this->invoiceService->voidInvoice($invoice, 'Barang salah beli / retur toko', $this->admin->id);

        $invoice->refresh();
        $this->assertEquals('void', $invoice->status);

        // Stok kembali bertambah 3 menjadi 10
        $this->filterOli->refresh();
        $this->assertEquals(10, $this->filterOli->qty_on_hand, 'Stok harus kembali +3 menjadi 10 setelah void (T-06, BR-S5).');

        // Pastikan tercatat SALE_RETURN
        $movementReturn = StockMovement::where('part_id', $this->filterOli->id)
            ->where('type', 'SALE_RETURN')
            ->latest('id')
            ->first();
        $this->assertNotNull($movementReturn);
        $this->assertEquals(3, $movementReturn->qty_change);
        $this->assertEquals(100000, $movementReturn->unit_cost_snapshot);
    }

    /**
     * BR-I1: Format nomor INV-YYMM-####, berurutan per bulan, unik.
     */
    public function test_br_i1_invoice_number_format_and_sequence(): void
    {
        $currentYm = now()->format('ym');

        $inv1 = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'items' => [['part_id' => $this->filterOli->id, 'qty' => 1]],
        ], $this->cashier->id);

        $inv2 = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'items' => [['part_id' => $this->filterOli->id, 'qty' => 1]],
        ], $this->cashier->id);

        $this->assertEquals("INV-{$currentYm}-0001", $inv1->invoice_number);
        $this->assertEquals("INV-{$currentYm}-0002", $inv2->invoice_number);
    }

    /**
     * BR-I3: Pajak dibaca dari pengaturan sistem (default 0), jangan di-hardcode.
     * 0 <= discount_amount <= subtotal.
     */
    public function test_br_i3_tax_calculation_reads_from_settings_and_never_hardcoded(): void
    {
        // Set tarif pajak 11% di tabel settings
        Setting::set('tax_rate_percent', 11);

        // Direct sale 1 x Filter Oli (150.000) diskon 50.000 -> DPP = 100.000, PPN 11% = 11.000, Total = 111.000
        $invoice = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'discount_amount' => 50000,
            'items' => [['part_id' => $this->filterOli->id, 'qty' => 1]],
        ], $this->cashier->id);

        $this->assertEquals(150000, $invoice->subtotal);
        $this->assertEquals(50000, $invoice->discount_amount);
        $this->assertEquals(11000, $invoice->tax_amount, 'Pajak 11% dari 100.000 harus 11.000 (BR-I3).');
        $this->assertEquals(111000, $invoice->total);

        // Kembalikan tarif pajak ke 0
        Setting::set('tax_rate_percent', 0);

        // Diskon lebih besar dari subtotal harus ditolak
        $this->expectException(InvalidInvoiceOperationException::class);
        $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'discount_amount' => 200000,
            'items' => [['part_id' => $this->filterOli->id, 'qty' => 1]],
        ], $this->cashier->id);
    }

    /**
     * BR-I4: Invoice dari order servis menyalin item order tanpa membuat pergerakan stok baru.
     */
    public function test_br_i4_invoice_from_work_order_does_not_create_new_stock_movement(): void
    {
        $wo = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customerMaju->id,
            'equipment_unit_id' => $this->unitMaju->id,
            'complaint' => 'Ganti filter oli',
        ], $this->admin->id);

        $this->workOrderService->addPartItem($wo, $this->filterOli->id, 1, null, $this->admin->id);
        $this->workOrderService->transitionStatus($wo, 'in_progress', $this->admin->id);
        $this->workOrderService->transitionStatus($wo, 'completed', $this->admin->id);

        $movementCountBefore = StockMovement::count();

        // Buat invoice dari WO
        $this->invoiceService->createFromWorkOrder($wo, [], $this->cashier->id);

        $movementCountAfter = StockMovement::count();
        $this->assertEquals($movementCountBefore, $movementCountAfter, 'Invoice dari WO tidak boleh membuat stock_movement baru (BR-I4).');
    }

    /**
     * BR-I7: due_date = issue_date + customer.payment_term_days.
     */
    public function test_br_i7_due_date_calculation_from_customer_payment_terms(): void
    {
        $this->customerMaju->update(['payment_term_days' => 45]);

        $invoice = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'issue_date' => '2026-05-01',
            'items' => [['part_id' => $this->filterOli->id, 'qty' => 1]],
        ], $this->cashier->id);

        $this->assertEquals('2026-05-01', $invoice->issue_date->toDateString());
        $this->assertEquals('2026-06-15', $invoice->due_date->toDateString(), 'Due date harus issue_date + 45 hari (BR-I7).');
    }

    /**
     * BR-I8: Invoice total = 0 tidak diperbolehkan.
     */
    public function test_br_i8_invoice_with_total_zero_is_rejected(): void
    {
        $this->expectException(InvalidInvoiceOperationException::class);
        $this->expectExceptionMessage('Total invoice harus lebih besar dari 0');

        // Diskon sama dengan subtotal (150.000) membuat total = 0
        $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'discount_amount' => 150000,
            'items' => [['part_id' => $this->filterOli->id, 'qty' => 1]],
        ], $this->cashier->id);
    }

    /**
     * BR-Y3: Tidak ada pembayaran untuk invoice void.
     */
    public function test_br_y3_cannot_pay_void_invoice(): void
    {
        $invoice = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'items' => [['part_id' => $this->filterOli->id, 'qty' => 1]],
        ], $this->cashier->id);

        $this->invoiceService->voidInvoice($invoice, 'Dibatalkan', $this->admin->id);

        $this->expectException(InvalidPaymentOperationException::class);
        $this->expectExceptionMessage('Tidak dapat mencatat pembayaran untuk invoice yang telah dibatalkan');

        $this->paymentService->recordPayment($invoice, [
            'amount' => 50000,
        ], $this->cashier->id);
    }

    /**
     * Uji konkurensi: Nomor invoice INV-YYMM-#### tidak duplikat saat dibuat secara paralel.
     * Menggunakan multi-process PHP worker nyata pada database SQLite dengan mode WAL.
     */
    public function test_parallel_invoice_number_generation_has_no_duplicates(): void
    {
        $tempDb = '/tmp/inv_concurrent_' . uniqid() . '.sqlite';
        @touch($tempDb);

        config(['database.connections.inv_sqlite' => [
            'driver' => 'sqlite',
            'database' => $tempDb,
            'prefix' => '',
            'busy_timeout' => 10000,
        ]]);
        DB::purge('inv_sqlite');

        Artisan::call('migrate', [
            '--database' => 'inv_sqlite',
            '--force' => true,
        ]);

        DB::connection('inv_sqlite')->statement('PRAGMA journal_mode=WAL;');
        DB::connection('inv_sqlite')->statement('PRAGMA busy_timeout=10000;');

        // Insert customer
        $custId = DB::connection('inv_sqlite')->table('customers')->insertGetId([
            'code' => 'CUST-PARALLEL',
            'name' => 'PT Concurrency Test',
            'type' => 'company',
            'payment_term_days' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert parts
        $partId = DB::connection('inv_sqlite')->table('parts')->insertGetId([
            'sku' => 'PRT-CONC-01',
            'name' => 'Baut Roda Heavy Duty',
            'category' => 'Hardware',
            'unit' => 'Pcs',
            'sell_price' => 25000,
            'avg_cost' => 15000,
            'qty_on_hand' => 100,
            'min_stock' => 5,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create initial stock movement
        DB::connection('inv_sqlite')->table('stock_movements')->insert([
            'part_id' => $partId,
            'type' => 'PURCHASE_IN',
            'qty_change' => 100,
            'unit_cost_snapshot' => 15000,
            'balance_after' => 100,
            'reason' => 'Stok awal',
            'created_at' => now(),
        ]);

        // Insert default setting tax
        DB::connection('inv_sqlite')->table('settings')->insert([
            'key' => 'tax_rate_percent',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workerScript = '/tmp/inv_worker_' . uniqid() . '.php';
        $scriptContent = <<<'WORKER'
<?php
$basePath = $argv[4];
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dbPath = $argv[1];
$custId = (int) $argv[2];
$partId = (int) $argv[3];
$workerId = $argv[5];

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $dbPath);
config(['database.connections.sqlite.database' => $dbPath]);
config(['database.connections.sqlite.busy_timeout' => 15000]);
\Illuminate\Support\Facades\DB::purge('sqlite');
\Illuminate\Support\Facades\DB::statement('PRAGMA journal_mode=WAL;');
\Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout=15000;');

$invoiceService = app(\App\Services\InvoiceService::class);

$createdNumbers = [];
for ($i = 0; $i < 3; $i++) {
    $retry = 0;
    while ($retry < 10) {
        try {
            $inv = $invoiceService->createDirectSale([
                'customer_id' => $custId,
                'items' => [['part_id' => $partId, 'qty' => 1]],
            ]);
            $createdNumbers[] = $inv->invoice_number;
            break;
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'database is locked')) {
                $retry++;
                usleep(rand(30000, 100000));
                continue;
            }
            throw $e;
        }
    }
}

file_put_contents("/tmp/inv_res_{$workerId}.json", json_encode($createdNumbers));
exit(0);
WORKER;
        file_put_contents($workerScript, $scriptContent);

        $appPath = base_path();
        $logA = "/tmp/inv_res_workerA.json";
        $logB = "/tmp/inv_res_workerB.json";
        @unlink($logA);
        @unlink($logB);

        $cmdA = "php {$workerScript} '{$tempDb}' {$custId} {$partId} '{$appPath}' workerA";
        $cmdB = "php {$workerScript} '{$tempDb}' {$custId} {$partId} '{$appPath}' workerB";

        $handleA = popen($cmdA, 'r');
        $handleB = popen($cmdB, 'r');

        pclose($handleA);
        pclose($handleB);

        $numsA = file_exists($logA) ? json_decode(file_get_contents($logA), true) : [];
        $numsB = file_exists($logB) ? json_decode(file_get_contents($logB), true) : [];

        $allNumbers = array_merge($numsA, $numsB);

        // Bersihkan file sementara
        @unlink($tempDb);
        @unlink($tempDb . '-wal');
        @unlink($tempDb . '-shm');
        @unlink($workerScript);
        @unlink($logA);
        @unlink($logB);

        $this->assertCount(6, $allNumbers, 'Harus berhasil membuat 6 invoice dari 2 worker paralel.');

        // Pastikan tidak ada satupun nomor invoice yang duplikat
        $uniqueNumbers = array_unique($allNumbers);
        $this->assertCount(
            count($allNumbers),
            $uniqueNumbers,
            'Nomor invoice tidak boleh ada yang duplikat saat dibuat secara paralel (BR-I1): ' . implode(', ', $allNumbers)
        );
    }

    /**
     * Memastikan nomor invoice dan nomor WO tidak pernah terpakai ulang (termasuk setelah void / pembatalan).
     */
    public function test_invoice_number_and_wo_number_never_reused_including_after_void(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-24 10:00:00'));

        // 1. Buat WO pertama
        $wo1 = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customerMaju->id,
            'equipment_unit_id' => $this->unitMaju->id,
            'complaint' => 'Servis rutin 1',
        ], $this->admin->id);
        $woNum1 = $wo1->wo_number;

        // Pasang item dan selesaikan WO-1
        $this->workOrderService->addPartItem($wo1, $this->filterOli->id, 1);
        $this->workOrderService->transitionStatus($wo1, 'in_progress', $this->admin->id);
        $this->workOrderService->transitionStatus($wo1, 'completed', $this->admin->id);

        // Terbitkan invoice pertama dari WO-1
        $inv1 = $this->invoiceService->createFromWorkOrder($wo1, [
            'issue_date' => '2026-10-24',
        ], $this->cashier->id);
        $invNum1 = $inv1->invoice_number;

        // Void invoice pertama
        $this->invoiceService->voidInvoice($inv1, 'Void untuk uji nomor urut', $this->admin->id);
        $this->assertEquals('void', $inv1->fresh()->status);

        // 2. Buat WO kedua
        $wo2 = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customerMaju->id,
            'equipment_unit_id' => $this->unitMaju->id,
            'complaint' => 'Servis rutin 2',
        ], $this->admin->id);
        $woNum2 = $wo2->wo_number;

        // Nomor WO kedua tidak boleh sama dengan WO pertama yang sudah pernah dipakai
        $this->assertNotEquals($woNum1, $woNum2, 'Nomor WO kedua tidak boleh menggunakan nomor WO pertama.');

        // Batalkan WO kedua
        $this->workOrderService->transitionStatus($wo2, 'cancelled', $this->admin->id);
        $this->assertEquals('cancelled', $wo2->fresh()->status);

        // 3. Buat WO ketiga (setelah WO-2 dibatalkan)
        $wo3 = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customerMaju->id,
            'equipment_unit_id' => $this->unitMaju->id,
            'complaint' => 'Servis rutin 3',
        ], $this->admin->id);
        $woNum3 = $wo3->wo_number;

        // Nomor WO ketiga tidak boleh memakai ulang nomor WO-1 maupun nomor WO-2 yang dibatalkan
        $this->assertNotEquals($woNum1, $woNum3);
        $this->assertNotEquals($woNum2, $woNum3);

        // 4. Terbitkan Invoice kedua (penjualan langsung) setelah Invoice-1 divoid
        $inv2 = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'issue_date' => '2026-10-24',
            'items' => [
                ['part_id' => $this->filterOli->id, 'qty' => 1],
            ],
        ], $this->cashier->id);
        $invNum2 = $inv2->invoice_number;

        // Nomor Invoice kedua tidak boleh menggunakan nomor invoice pertama yang divoid!
        $this->assertNotEquals($invNum1, $invNum2, 'Nomor invoice kedua tidak boleh memakai ulang nomor invoice pertama yang telah divoid (BR-I1).');

        // 5. Terbitkan Invoice ketiga
        $inv3 = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'issue_date' => '2026-10-24',
            'items' => [
                ['part_id' => $this->filterOli->id, 'qty' => 1],
            ],
        ], $this->cashier->id);
        $invNum3 = $inv3->invoice_number;

        // Void Invoice ketiga
        $this->invoiceService->voidInvoice($inv3, 'Void invoice ketiga', $this->admin->id);

        // 6. Terbitkan Invoice keempat
        $inv4 = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'issue_date' => '2026-10-24',
            'items' => [
                ['part_id' => $this->filterOli->id, 'qty' => 1],
            ],
        ], $this->cashier->id);
        $invNum4 = $inv4->invoice_number;

        // Pastikan nomor Invoice keempat tidak mendaur ulang nomor invoice ketiga yang divoid
        $this->assertNotEquals($invNum3, $invNum4);

        // Seluruh 4 invoice dan 3 work order harus memiliki nomor unik dan tersimpan di database
        $allInvoiceNumbers = [$invNum1, $invNum2, $invNum3, $invNum4];
        $this->assertCount(4, array_unique($allInvoiceNumbers));

        $allWoNumbers = [$woNum1, $woNum2, $woNum3];
        $this->assertCount(3, array_unique($allWoNumbers));

        Carbon::setTestNow();
    }

    /**
     * Memastikan aksi void pada suatu invoice tidak mengubah nilai atau status invoice lainnya.
     */
    public function test_voiding_an_invoice_does_not_modify_or_affect_other_invoices(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-24 10:00:00'));

        $customerB = Customer::create([
            'code' => 'CUST-B',
            'name' => 'PT Mandiri Perkasa B',
            'type' => 'company',
            'payment_term_days' => 14,
        ]);

        // Invoice 1 (Customer Maju): Penjualan langsung 2 unit Filter Oli = 300.000.
        // Dibayar sebagian 100.000 -> status partially_paid, sisa 200.000.
        $invA = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'issue_date' => '2026-10-24',
            'items' => [
                ['part_id' => $this->filterOli->id, 'qty' => 2],
            ],
        ], $this->cashier->id);

        $this->paymentService->recordPayment($invA, [
            'amount' => 100000,
            'paid_at' => '2026-10-24',
            'method' => 'cash',
        ], $this->cashier->id);

        $invA->refresh();
        $this->assertEquals('partially_paid', $invA->status);
        $this->assertEquals(200000, $invA->remainingBalance());

        // Invoice 2 (Customer B): Penjualan langsung 3 unit Filter Oli = 450.000.
        // Belum ada pembayaran -> status issued, sisa 450.000.
        $invB = $this->invoiceService->createDirectSale([
            'customer_id' => $customerB->id,
            'issue_date' => '2026-10-24',
            'items' => [
                ['part_id' => $this->filterOli->id, 'qty' => 3],
            ],
        ], $this->cashier->id);

        $invB->refresh();
        $this->assertEquals('issued', $invB->status);
        $this->assertEquals(450000, $invB->remainingBalance());

        // Invoice 3 (Customer Maju): Penjualan langsung 1 unit Filter Oli = 150.000.
        // Dibayar lunas 150.000 -> status paid, sisa 0.
        $invC = $this->invoiceService->createDirectSale([
            'customer_id' => $this->customerMaju->id,
            'issue_date' => '2026-10-24',
            'items' => [
                ['part_id' => $this->filterOli->id, 'qty' => 1],
            ],
        ], $this->cashier->id);

        $this->paymentService->recordPayment($invC, [
            'amount' => 150000,
            'paid_at' => '2026-10-24',
            'method' => 'transfer',
        ], $this->cashier->id);

        $invC->refresh();
        $this->assertEquals('paid', $invC->status);
        $this->assertEquals(0, $invC->remainingBalance());

        // Ambil snapshot atribut Invoice A dan Invoice C sebelum void Invoice B
        $invASnapshot = [
            'status' => $invA->status,
            'subtotal' => $invA->subtotal,
            'discount_amount' => $invA->discount_amount,
            'tax_amount' => $invA->tax_amount,
            'total' => $invA->total,
            'remaining' => $invA->remainingBalance(),
            'valid_payments_count' => $invA->validPayments()->count(),
            'valid_payments_sum' => (int) $invA->validPayments()->sum('amount'),
        ];

        $invCSnapshot = [
            'status' => $invC->status,
            'subtotal' => $invC->subtotal,
            'discount_amount' => $invC->discount_amount,
            'tax_amount' => $invC->tax_amount,
            'total' => $invC->total,
            'remaining' => $invC->remainingBalance(),
            'valid_payments_count' => $invC->validPayments()->count(),
            'valid_payments_sum' => (int) $invC->validPayments()->sum('amount'),
        ];

        // EKSEKUSI: Void Invoice B oleh Admin
        $this->invoiceService->voidInvoice($invB, 'Customer B membatalkan pesanan', $this->admin->id);
        $this->assertEquals('void', $invB->fresh()->status);

        // VERIFIKASI: Invoice A tidak boleh berubah sama sekali
        $invA->refresh();
        $this->assertEquals($invASnapshot['status'], $invA->status, 'Status Invoice A tidak boleh berubah.');
        $this->assertEquals($invASnapshot['subtotal'], $invA->subtotal);
        $this->assertEquals($invASnapshot['total'], $invA->total);
        $this->assertEquals($invASnapshot['remaining'], $invA->remainingBalance());
        $this->assertEquals($invASnapshot['valid_payments_count'], $invA->validPayments()->count());
        $this->assertEquals($invASnapshot['valid_payments_sum'], (int) $invA->validPayments()->sum('amount'));

        // VERIFIKASI: Invoice C tidak boleh berubah sama sekali
        $invC->refresh();
        $this->assertEquals($invCSnapshot['status'], $invC->status, 'Status Invoice C tidak boleh berubah.');
        $this->assertEquals($invCSnapshot['subtotal'], $invC->subtotal);
        $this->assertEquals($invCSnapshot['total'], $invC->total);
        $this->assertEquals($invCSnapshot['remaining'], $invC->remainingBalance());
        $this->assertEquals($invCSnapshot['valid_payments_count'], $invC->validPayments()->count());
        $this->assertEquals($invCSnapshot['valid_payments_sum'], (int) $invC->validPayments()->sum('amount'));

        // VERIFIKASI PIUTANG: Piutang Customer Maju tetap 200.000, Piutang Customer B menjadi 0 (karena invB void)
        $reportService = app(\App\Services\ReportService::class);
        $receivables = $reportService->getReceivablesReport('2026-10-24');
        $custSummary = collect($receivables['customer_summary']);

        $majuRow = $custSummary->first(fn ($row) => $row['customer']->id === $this->customerMaju->id);
        $this->assertNotNull($majuRow);
        $this->assertEquals(200000, $majuRow['total_receivable'], 'Piutang PT Maju harus tetap 200.000.');

        $bRow = $custSummary->first(fn ($row) => $row['customer']->id === $customerB->id);
        $this->assertNull($bRow, 'Customer B tidak boleh memiliki piutang aktif karena satu-satunya faktur telah divoid.');

        Carbon::setTestNow();
    }
}
