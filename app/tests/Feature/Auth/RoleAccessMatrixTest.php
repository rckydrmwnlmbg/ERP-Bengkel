<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use App\Models\EquipmentUnit;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $mechanic;

    protected Customer $customer;
    protected EquipmentUnit $equipmentUnit;
    protected Part $part;
    protected Service $service;
    protected Supplier $supplier;
    protected Purchase $purchase;
    protected WorkOrder $workOrder;
    protected Invoice $invoice;
    protected Payment $payment;
    protected Expense $expense;

    protected int $secretHpp = 789123;
    protected int $publicSellPrice = 987654;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['email' => 'admin@test.com', 'name' => 'Admin User']);
        $this->cashier = User::factory()->cashier()->create(['email' => 'kasir@test.com', 'name' => 'Kasir User']);
        $this->mechanic = User::factory()->mechanic()->create(['email' => 'mekanik@test.com', 'name' => 'Mekanik User']);

        $this->customer = Customer::create([
            'code' => 'CUST-TEST',
            'name' => 'PT Maju Mandiri Test',
            'type' => 'company',
            'phone' => '08123456789',
            'address' => 'Jl. Industri No. 1',
            'payment_term_days' => 30,
        ]);

        $this->equipmentUnit = EquipmentUnit::create([
            'customer_id' => $this->customer->id,
            'brand' => 'Komatsu',
            'model' => 'PC200-8',
            'unit_code' => 'EXC-01',
            'serial_number' => 'KM-998877',
        ]);

        $this->part = Part::create([
            'sku' => 'SKU-SECRET-01',
            'name' => 'Hydraulic Cylinder Seal Secret',
            'category' => 'Hidrolik',
            'unit' => 'Pcs',
            'sell_price' => $this->publicSellPrice,
            'avg_cost' => $this->secretHpp,
            'qty_on_hand' => 20,
            'min_stock' => 5,
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'code' => 'SRV-TEST-01',
            'name' => 'Overhaul Silinder Utama',
            'default_price' => 1500000,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'PT Distributor Hidrolik Utama',
            'phone' => '021-5551234',
            'address' => 'Jakarta Barat',
        ]);

        $this->purchase = Purchase::create([
            'supplier_id' => $this->supplier->id,
            'purchase_date' => Carbon::now()->toDateString(),
            'reference' => 'PO-TEST-001',
            'total' => 5000000,
            'created_by' => $this->admin->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $this->purchase->id,
            'part_id' => $this->part->id,
            'qty' => 5,
            'unit_cost' => $this->secretHpp,
        ]);

        StockMovement::create([
            'part_id' => $this->part->id,
            'type' => 'PURCHASE_IN',
            'qty_change' => 5,
            'unit_cost_snapshot' => $this->secretHpp,
            'balance_after' => 20,
            'ref_type' => 'Purchase',
            'ref_id' => $this->purchase->id,
            'reason' => 'Pengadaan stok awal',
            'created_by' => $this->admin->id,
            'created_at' => Carbon::now(),
        ]);

        $this->workOrder = WorkOrder::create([
            'wo_number' => 'WO-2610-0099',
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->equipmentUnit->id,
            'mechanic_id' => $this->mechanic->id,
            'status' => 'completed',
            'complaint' => 'Kebocoran oli pada silinder arm',
            'diagnosis' => 'Seal aus, ganti seal kit',
            'opened_at' => Carbon::now()->subDays(2),
            'completed_at' => Carbon::now()->subDay(),
            'created_by' => $this->mechanic->id,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $this->workOrder->id,
            'item_type' => 'part',
            'part_id' => $this->part->id,
            'qty' => 1,
            'unit_price' => $this->publicSellPrice,
            'unit_cost_snapshot' => $this->secretHpp,
            'line_total' => $this->publicSellPrice,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $this->workOrder->id,
            'item_type' => 'service',
            'service_id' => $this->service->id,
            'qty' => 1,
            'unit_price' => 1500000,
            'unit_cost_snapshot' => 0,
            'line_total' => 1500000,
        ]);

        $this->invoice = Invoice::create([
            'invoice_number' => 'INV-2610-0099',
            'customer_id' => $this->customer->id,
            'source_type' => 'work_order',
            'work_order_id' => $this->workOrder->id,
            'issue_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'subtotal' => $this->publicSellPrice + 1500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => $this->publicSellPrice + 1500000,
            'status' => 'partially_paid',
            'created_by' => $this->cashier->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'item_type' => 'part',
            'part_id' => $this->part->id,
            'qty' => 1,
            'unit_price' => $this->publicSellPrice,
            'unit_cost_snapshot' => $this->secretHpp,
            'line_total' => $this->publicSellPrice,
        ]);

        $this->payment = Payment::create([
            'invoice_id' => $this->invoice->id,
            'amount' => 500000,
            'paid_at' => Carbon::now()->toDateString(),
            'method' => 'transfer',
            'reference' => 'TRF-TEST-01',
            'status' => 'valid',
            'created_by' => $this->cashier->id,
        ]);

        $this->expense = Expense::create([
            'expense_date' => Carbon::now()->toDateString(),
            'category' => 'Listrik & Air',
            'amount' => 1250000,
            'description' => 'Tagihan PLN bengkel bulan ini',
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * Matriks URL: Admin memiliki akses ke seluruh fitur dan modul.
     */
    public function test_admin_has_full_access_to_all_urls(): void
    {
        $this->actingAs($this->admin);

        // Dashboard & Master Data
        $this->get('/dashboard')->assertOk();
        $this->get('/customers')->assertOk();
        $this->get('/customers/create')->assertOk();
        $this->get("/customers/{$this->customer->id}")->assertOk();
        $this->get("/customers/{$this->customer->id}/edit")->assertOk();
        $this->get('/parts')->assertOk();
        $this->get('/parts/create')->assertOk();
        $this->get("/parts/{$this->part->id}")->assertOk();
        $this->get("/parts/{$this->part->id}/edit")->assertOk();
        $this->get("/parts/{$this->part->id}/stock-card")->assertOk();
        $this->get('/services')->assertOk();
        $this->get('/services/create')->assertOk();
        $this->get('/suppliers')->assertOk();
        $this->get('/suppliers/create')->assertOk();

        // Barang Masuk & Penyesuaian Stok
        $this->get('/purchases')->assertOk();
        $this->get('/purchases/create')->assertOk();
        $this->get("/purchases/{$this->purchase->id}")->assertOk();
        $this->get('/stock-adjustments')->assertOk();
        $this->get('/stock-adjustments/create')->assertOk();

        // Order Servis & Penjualan Langsung
        $this->get('/work-orders')->assertOk();
        $this->get('/work-orders/create')->assertOk();
        $this->get("/work-orders/{$this->workOrder->id}")->assertOk();
        $this->get("/work-orders/{$this->workOrder->id}/edit")->assertOk();
        $this->get('/direct-sales/create')->assertOk();

        // Invoice & Pembayaran & Piutang
        $this->get('/invoices')->assertOk();
        $this->get("/invoices/{$this->invoice->id}")->assertOk();
        $this->get("/invoices/{$this->invoice->id}/print")->assertOk();
        $this->get('/receivables')->assertOk();
        $this->get('/reports/receivables')->assertOk();

        // Pengeluaran & Laporan Finansial
        $this->get('/expenses')->assertOk();
        $this->get('/expenses/create')->assertOk();
        $this->get('/reports/revenue')->assertOk();
        $this->get('/reports/profit-loss')->assertOk();
        $this->get('/reports/cash-flow')->assertOk();
        $this->get('/reports/export/revenue')->assertOk();
        $this->get('/reports/export/profit-loss')->assertOk();
        $this->get('/reports/export/cash-flow')->assertOk();
        $this->get('/reports/export/receivables')->assertOk();
        $this->get('/reports/print/revenue')->assertOk();
        $this->get('/reports/print/profit-loss')->assertOk();
        $this->get('/reports/print/cash-flow')->assertOk();
        $this->get('/reports/print/receivables')->assertOk();

        // Audit Log & Pengaturan
        $this->get('/audit-logs')->assertOk();
        $this->get('/settings')->assertOk();
    }

    /**
     * Matriks URL: Kasir dilarang membuka laporan keuangan non-piutang, barang masuk,
     * penyesuaian stok, pengeluaran, audit log, pengaturan, dan dilarang void.
     */
    public function test_cashier_access_restrictions(): void
    {
        $this->actingAs($this->cashier);

        // Kasir DILARANG membuka laporan laba rugi, arus kas, dan omzet (403)
        $this->get('/reports/profit-loss')->assertForbidden();
        $this->get('/reports/cash-flow')->assertForbidden();
        $this->get('/reports/revenue')->assertForbidden();
        $this->get('/reports/export/profit-loss')->assertForbidden();
        $this->get('/reports/export/cash-flow')->assertForbidden();
        $this->get('/reports/export/revenue')->assertForbidden();
        $this->get('/reports/print/profit-loss')->assertForbidden();
        $this->get('/reports/print/cash-flow')->assertForbidden();
        $this->get('/reports/print/revenue')->assertForbidden();

        // Kasir DILARANG membuka barang masuk (purchases) (403)
        $this->get('/purchases')->assertForbidden();
        $this->get('/purchases/create')->assertForbidden();
        $this->get("/purchases/{$this->purchase->id}")->assertForbidden();
        $this->post('/purchases', [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => Carbon::now()->toDateString(),
            'items' => [['part_id' => $this->part->id, 'qty' => 1, 'unit_cost' => 500000]],
        ])->assertForbidden();

        // Kasir DILARANG membuka penyesuaian stok (403)
        $this->get('/stock-adjustments')->assertForbidden();
        $this->get('/stock-adjustments/create')->assertForbidden();
        $this->post('/stock-adjustments', [
            'part_id' => $this->part->id,
            'type' => 'ADJUSTMENT_IN',
            'qty' => 1,
            'reason' => 'Tes selisih',
        ])->assertForbidden();

        // Kasir DILARANG membuka modul pengeluaran (expenses) (403)
        $this->get('/expenses')->assertForbidden();
        $this->get('/expenses/create')->assertForbidden();
        $this->post('/expenses', [
            'expense_date' => Carbon::now()->toDateString(),
            'category' => 'Bensin',
            'amount' => 50000,
        ])->assertForbidden();

        // Kasir DILARANG membuka audit log dan pengaturan (403)
        $this->get('/audit-logs')->assertForbidden();
        $this->get('/settings')->assertForbidden();
        $this->post('/settings', ['shop_name' => 'Nama Baru', 'tax_rate_percent' => 0])->assertForbidden();

        // Kasir DILARANG melakukan aksi void invoice dan void pembayaran (403)
        $this->post("/invoices/{$this->invoice->id}/void", ['reason' => 'Salah tagih kasir'])->assertForbidden();
        $this->post("/payments/{$this->payment->id}/void", ['reason' => 'Batal transfer kasir'])->assertForbidden();

        // Kasir DILARANG membuat/mengubah order servis secara teknis (403)
        $this->get('/work-orders/create')->assertForbidden();
        $this->post('/work-orders', [
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->equipmentUnit->id,
            'complaint' => 'Tes kasir buat wo',
        ])->assertForbidden();
        $this->get("/work-orders/{$this->workOrder->id}/edit")->assertForbidden();
        $this->post("/work-orders/{$this->workOrder->id}/status", ['status' => 'cancelled'])->assertForbidden();
        $this->post("/work-orders/{$this->workOrder->id}/parts", ['part_id' => $this->part->id, 'qty' => 1])->assertForbidden();

        // Kasir DILARANG edit master part, jasa, dan supplier (403)
        $this->get('/parts/create')->assertForbidden();
        $this->get("/parts/{$this->part->id}/edit")->assertForbidden();
        $this->get('/services/create')->assertForbidden();
        $this->get('/suppliers/create')->assertForbidden();
        $this->get("/customers/{$this->customer->id}/edit")->assertForbidden();

        // Kasir DIIZINKAN: Dashboard, Invoice, Penjualan Langsung, Pembayaran, Laporan Piutang, Tambah Customer
        $this->get('/dashboard')->assertOk();
        $this->get('/customers')->assertOk();
        $this->get('/customers/create')->assertOk();
        $this->get("/customers/{$this->customer->id}")->assertOk();
        $this->get('/direct-sales/create')->assertOk();
        $this->get('/invoices')->assertOk();
        $this->get("/invoices/{$this->invoice->id}")->assertOk();
        $this->get("/invoices/{$this->invoice->id}/print")->assertOk();
        $this->get('/receivables')->assertOk();
        $this->get('/reports/receivables')->assertOk();
        $this->get('/reports/export/receivables')->assertOk();
        $this->get('/reports/print/receivables')->assertOk();
        $this->get('/work-orders')->assertOk();
        $this->get("/work-orders/{$this->workOrder->id}")->assertOk();
    }

    /**
     * Matriks URL: Mekanik dilarang membuka invoice, pembayaran, penjualan toko, laporan finansial,
     * audit log, pengaturan, barang masuk, penyesuaian stok, pengeluaran, dan tambah customer.
     */
    public function test_mechanic_access_restrictions(): void
    {
        $this->actingAs($this->mechanic);

        // Mekanik DILARANG membuka invoice dan penjualan langsung (403)
        $this->get('/invoices')->assertForbidden();
        $this->get("/invoices/{$this->invoice->id}")->assertForbidden();
        $this->get("/invoices/{$this->invoice->id}/print")->assertForbidden();
        $this->get("/invoices/from-work-order/{$this->workOrder->id}")->assertForbidden();
        $this->post("/invoices/from-work-order/{$this->workOrder->id}", ['issue_date' => Carbon::now()->toDateString()])->assertForbidden();
        $this->get('/direct-sales/create')->assertForbidden();
        $this->post('/direct-sales', [
            'customer_id' => $this->customer->id,
            'items' => [['part_id' => $this->part->id, 'qty' => 1]],
        ])->assertForbidden();
        $this->post("/invoices/{$this->invoice->id}/void", ['reason' => 'Void oleh mekanik'])->assertForbidden();

        // Mekanik DILARANG mencatat pembayaran atau void pembayaran (403)
        $this->post("/invoices/{$this->invoice->id}/payments", [
            'paid_at' => Carbon::now()->toDateString(),
            'amount' => 100000,
            'method' => 'cash',
        ])->assertForbidden();
        $this->post("/payments/{$this->payment->id}/void", ['reason' => 'Void pembayaran mekanik'])->assertForbidden();

        // Mekanik DILARANG membuka piutang dan semua laporan (403)
        $this->get('/receivables')->assertForbidden();
        $this->get('/reports/receivables')->assertForbidden();
        $this->get('/reports/revenue')->assertForbidden();
        $this->get('/reports/profit-loss')->assertForbidden();
        $this->get('/reports/cash-flow')->assertForbidden();
        $this->get('/reports/export/receivables')->assertForbidden();
        $this->get('/reports/export/profit-loss')->assertForbidden();
        $this->get('/reports/print/receivables')->assertForbidden();
        $this->get('/reports/print/profit-loss')->assertForbidden();

        // Mekanik DILARANG membuka audit log, pengaturan, pengeluaran, barang masuk, stok opname (403)
        $this->get('/audit-logs')->assertForbidden();
        $this->get('/settings')->assertForbidden();
        $this->post('/settings', ['shop_name' => 'Bengkel Mekanik', 'tax_rate_percent' => 0])->assertForbidden();
        $this->get('/expenses')->assertForbidden();
        $this->get('/expenses/create')->assertForbidden();
        $this->get('/purchases')->assertForbidden();
        $this->get('/purchases/create')->assertForbidden();
        $this->get("/purchases/{$this->purchase->id}")->assertForbidden();
        $this->get('/stock-adjustments')->assertForbidden();
        $this->get('/stock-adjustments/create')->assertForbidden();

        // Mekanik DILARANG tambah atau edit customer/part/jasa/supplier (403)
        $this->get('/customers/create')->assertForbidden();
        $this->post('/customers', ['name' => 'PT Baru Mekanik', 'phone' => '0812'])->assertForbidden();
        $this->get("/customers/{$this->customer->id}/edit")->assertForbidden();
        $this->get('/parts/create')->assertForbidden();
        $this->get("/parts/{$this->part->id}/edit")->assertForbidden();
        $this->get('/services/create')->assertForbidden();
        $this->get('/suppliers/create')->assertForbidden();

        // Mekanik DIIZINKAN: Dashboard, Order Servis (CRUD), dan Lihat Master Data
        $this->get('/dashboard')->assertOk();
        $this->get('/customers')->assertOk();
        $this->get("/customers/{$this->customer->id}")->assertOk();
        $this->get('/parts')->assertOk();
        $this->get("/parts/{$this->part->id}")->assertOk();
        $this->get("/parts/{$this->part->id}/stock-card")->assertOk();
        $this->get('/services')->assertOk();
        $this->get('/suppliers')->assertOk();
        $this->get('/work-orders')->assertOk();
        $this->get('/work-orders/create')->assertOk();
        $this->get("/work-orders/{$this->workOrder->id}")->assertOk();
    }

    /**
     * Proteksi Kolom HPP / avg_cost: Pastikan nilai dan header HPP tidak bocor ke HTML untuk Kasir dan Mekanik.
     */
    public function test_cost_price_and_hpp_are_never_sent_in_html_to_cashier_or_mechanic(): void
    {
        $formattedHpp = '789.123';
        $rawHpp = '789123';
        $formattedSellPrice = '987.654';

        // 1. Verifikasi Admin: Admin WAJIB melihat HPP di semua halaman
        $this->actingAs($this->admin);

        $adminParts = $this->get('/parts');
        $adminParts->assertOk();
        $this->assertStringContainsString('HPP / Avg Cost', $adminParts->getContent());
        $this->assertStringContainsString($formattedHpp, $adminParts->getContent());

        $adminPartShow = $this->get("/parts/{$this->part->id}");
        $adminPartShow->assertOk();
        $this->assertStringContainsString('HPP / Avg Cost (Admin)', $adminPartShow->getContent());
        $this->assertStringContainsString($formattedHpp, $adminPartShow->getContent());

        $adminStockCard = $this->get("/parts/{$this->part->id}/stock-card");
        $adminStockCard->assertOk();
        $this->assertStringContainsString('Snapshot HPP (BR-S8)', $adminStockCard->getContent());
        $this->assertStringContainsString($formattedHpp, $adminStockCard->getContent());

        $adminWorkOrder = $this->get("/work-orders/{$this->workOrder->id}");
        $adminWorkOrder->assertOk();
        $this->assertStringContainsString('Snapshot HPP (BR-S8)', $adminWorkOrder->getContent());
        $this->assertStringContainsString($formattedHpp, $adminWorkOrder->getContent());

        $adminInvoice = $this->get("/invoices/{$this->invoice->id}");
        $adminInvoice->assertOk();
        $this->assertStringContainsString('Snapshot HPP', $adminInvoice->getContent());
        $this->assertStringContainsString($formattedHpp, $adminInvoice->getContent());

        // 2. Verifikasi Kasir: Kasir TIDAK BOLEH menerima HPP / avg_cost / Snapshot HPP dalam isi HTML respons
        $this->actingAs($this->cashier);

        // a. Daftar Sparepart (/parts)
        $cashierParts = $this->get('/parts');
        $cashierParts->assertOk();
        $cashierPartsHtml = $cashierParts->getContent();
        $this->assertStringContainsString($formattedSellPrice, $cashierPartsHtml, 'Kasir harus dapat melihat harga jual');
        $this->assertStringNotContainsString('HPP / Avg Cost', $cashierPartsHtml);
        $this->assertStringNotContainsString($formattedHpp, $cashierPartsHtml);
        $this->assertStringNotContainsString($rawHpp, $cashierPartsHtml);

        // b. Detail Sparepart (/parts/{id})
        $cashierPartShow = $this->get("/parts/{$this->part->id}");
        $cashierPartShow->assertOk();
        $cashierPartShowHtml = $cashierPartShow->getContent();
        $this->assertStringContainsString($formattedSellPrice, $cashierPartShowHtml);
        $this->assertStringNotContainsString('HPP / Avg Cost', $cashierPartShowHtml);
        $this->assertStringNotContainsString($formattedHpp, $cashierPartShowHtml);
        $this->assertStringNotContainsString($rawHpp, $cashierPartShowHtml);
        $this->assertStringNotContainsString('Margin:', $cashierPartShowHtml);

        // c. Kartu Stok Sparepart (/parts/{id}/stock-card)
        $cashierStockCard = $this->get("/parts/{$this->part->id}/stock-card");
        $cashierStockCard->assertOk();
        $cashierStockCardHtml = $cashierStockCard->getContent();
        $this->assertStringNotContainsString('Snapshot HPP', $cashierStockCardHtml);
        $this->assertStringNotContainsString('HPP Rata-rata', $cashierStockCardHtml);
        $this->assertStringNotContainsString($formattedHpp, $cashierStockCardHtml);
        $this->assertStringNotContainsString($rawHpp, $cashierStockCardHtml);

        // d. Detail Order Servis (/work-orders/{id})
        $cashierWorkOrder = $this->get("/work-orders/{$this->workOrder->id}");
        $cashierWorkOrder->assertOk();
        $cashierWorkOrderHtml = $cashierWorkOrder->getContent();
        $this->assertStringContainsString($formattedSellPrice, $cashierWorkOrderHtml);
        $this->assertStringNotContainsString('Snapshot HPP', $cashierWorkOrderHtml);
        $this->assertStringNotContainsString($formattedHpp, $cashierWorkOrderHtml);
        $this->assertStringNotContainsString($rawHpp, $cashierWorkOrderHtml);

        // e. Detail Faktur Tagihan (/invoices/{id})
        $cashierInvoice = $this->get("/invoices/{$this->invoice->id}");
        $cashierInvoice->assertOk();
        $cashierInvoiceHtml = $cashierInvoice->getContent();
        $this->assertStringContainsString($formattedSellPrice, $cashierInvoiceHtml);
        $this->assertStringNotContainsString('Snapshot HPP', $cashierInvoiceHtml);
        $this->assertStringNotContainsString($formattedHpp, $cashierInvoiceHtml);
        $this->assertStringNotContainsString($rawHpp, $cashierInvoiceHtml);

        // 3. Verifikasi Mekanik: Mekanik TIDAK BOLEH menerima HPP / avg_cost / Snapshot HPP dalam isi HTML respons
        $this->actingAs($this->mechanic);

        // a. Daftar Sparepart (/parts)
        $mechanicParts = $this->get('/parts');
        $mechanicParts->assertOk();
        $mechanicPartsHtml = $mechanicParts->getContent();
        $this->assertStringContainsString($formattedSellPrice, $mechanicPartsHtml);
        $this->assertStringNotContainsString('HPP / Avg Cost', $mechanicPartsHtml);
        $this->assertStringNotContainsString($formattedHpp, $mechanicPartsHtml);
        $this->assertStringNotContainsString($rawHpp, $mechanicPartsHtml);

        // b. Detail Sparepart (/parts/{id})
        $mechanicPartShow = $this->get("/parts/{$this->part->id}");
        $mechanicPartShow->assertOk();
        $mechanicPartShowHtml = $mechanicPartShow->getContent();
        $this->assertStringContainsString($formattedSellPrice, $mechanicPartShowHtml);
        $this->assertStringNotContainsString('HPP / Avg Cost', $mechanicPartShowHtml);
        $this->assertStringNotContainsString($formattedHpp, $mechanicPartShowHtml);
        $this->assertStringNotContainsString($rawHpp, $mechanicPartShowHtml);
        $this->assertStringNotContainsString('Margin:', $mechanicPartShowHtml);

        // c. Kartu Stok Sparepart (/parts/{id}/stock-card)
        $mechanicStockCard = $this->get("/parts/{$this->part->id}/stock-card");
        $mechanicStockCard->assertOk();
        $mechanicStockCardHtml = $mechanicStockCard->getContent();
        $this->assertStringNotContainsString('Snapshot HPP', $mechanicStockCardHtml);
        $this->assertStringNotContainsString('HPP Rata-rata', $mechanicStockCardHtml);
        $this->assertStringNotContainsString($formattedHpp, $mechanicStockCardHtml);
        $this->assertStringNotContainsString($rawHpp, $mechanicStockCardHtml);

        // d. Detail Order Servis (/work-orders/{id})
        $mechanicWorkOrder = $this->get("/work-orders/{$this->workOrder->id}");
        $mechanicWorkOrder->assertOk();
        $mechanicWorkOrderHtml = $mechanicWorkOrder->getContent();
        $this->assertStringContainsString($formattedSellPrice, $mechanicWorkOrderHtml);
        $this->assertStringNotContainsString('Snapshot HPP', $mechanicWorkOrderHtml);
        $this->assertStringNotContainsString($formattedHpp, $mechanicWorkOrderHtml);
        $this->assertStringNotContainsString($rawHpp, $mechanicWorkOrderHtml);
    }
}
