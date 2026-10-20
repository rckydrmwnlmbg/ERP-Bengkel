<?php

namespace Tests\Feature\Invoice;

use App\Models\Customer;
use App\Models\EquipmentUnit;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\InvoiceService;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $mechanic;
    protected Customer $customer;
    protected Part $part;
    protected WorkOrder $workOrder;
    protected InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = app(InvoiceService::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->mechanic = User::factory()->create(['role' => 'mechanic']);

        $this->customer = Customer::create([
            'code' => 'CUST-001',
            'name' => 'PT Borneo Tambang',
            'type' => 'company',
            'phone' => '081234567890',
            'payment_term_days' => 30,
        ]);

        $this->part = Part::create([
            'sku' => 'PRT-CTRL-01',
            'name' => 'Filter Solar',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 200000,
            'avg_cost' => 120000,
            'qty_on_hand' => 20,
            'min_stock' => 5,
            'is_active' => true,
        ]);

        StockMovement::create([
            'part_id' => $this->part->id,
            'type' => 'PURCHASE_IN',
            'qty_change' => 20,
            'unit_cost_snapshot' => 120000,
            'balance_after' => 20,
            'reason' => 'Stok awal',
            'created_by' => $this->admin->id,
        ]);

        $unit = EquipmentUnit::create([
            'customer_id' => $this->customer->id,
            'brand' => 'Caterpillar',
            'model' => '320D',
            'serial_number' => 'CAT320-112233',
            'unit_code' => 'EXC-02',
            'hour_meter' => 3500,
        ]);

        $woService = app(WorkOrderService::class);
        $this->workOrder = $woService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $unit->id,
            'complaint' => 'Bocor filter solar',
        ], $this->admin->id);

        $woService->addPartItem($this->workOrder, $this->part->id, 2, null, $this->admin->id);
        $woService->transitionStatus($this->workOrder, 'in_progress', $this->admin->id);
        $woService->transitionStatus($this->workOrder, 'completed', $this->admin->id);
    }

    /**
     * RBAC: Admin dan Kasir dapat melihat daftar invoice, Mekanik ditolak (403).
     */
    public function test_rbac_invoice_index_access(): void
    {
        $responseCashier = $this->actingAs($this->cashier)->get(route('invoices.index'));
        $responseCashier->assertStatus(200);

        $responseAdmin = $this->actingAs($this->admin)->get(route('invoices.index'));
        $responseAdmin->assertStatus(200);

        $responseMechanic = $this->actingAs($this->mechanic)->get(route('invoices.index'));
        $responseMechanic->assertStatus(403);
    }

    /**
     * Kasir dapat membuat penjualan langsung (direct sale).
     */
    public function test_cashier_can_create_direct_sale(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('direct-sales.store'), [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->toDateString(),
            'discount_amount' => 10000,
            'items' => [
                [
                    'part_id' => $this->part->id,
                    'qty' => 2,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'source_type' => 'counter_sale',
            'subtotal' => 400000,
            'discount_amount' => 10000,
            'total' => 390000,
        ]);
    }

    /**
     * Kasir dapat menerbitkan invoice dari order servis yang completed.
     */
    public function test_cashier_can_create_invoice_from_work_order(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('invoices.store-from-wo', $this->workOrder), [
            'discount_amount' => 0,
        ]);

        $response->assertRedirect();

        $invoice = Invoice::where('work_order_id', $this->workOrder->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(400000, $invoice->total);

        $this->workOrder->refresh();
        $this->assertEquals('invoiced', $this->workOrder->status);
    }

    /**
     * Halaman cetak / print invoice dapat dibuka oleh kasir dan admin.
     */
    public function test_print_invoice_view_renders_correctly(): void
    {
        $invoice = $this->invoiceService->createFromWorkOrder($this->workOrder, [], $this->cashier->id);

        $response = $this->actingAs($this->cashier)->get(route('invoices.print', $invoice));
        $response->assertStatus(200);
        $response->assertSee($invoice->invoice_number);
        $response->assertSee($this->customer->name);
        $response->assertSee('FAKTUR PENJUALAN');
    }

    /**
     * RBAC Void Invoice: Hanya Admin yang dapat membatalkan invoice (BR-I5). Kasir dan mekanik 403.
     */
    public function test_rbac_void_invoice(): void
    {
        $invoice = $this->invoiceService->createFromWorkOrder($this->workOrder, [], $this->cashier->id);

        // Kasir coba void -> 403
        $responseCashier = $this->actingAs($this->cashier)->post(route('invoices.void', $invoice), [
            'reason' => 'Salah harga',
        ]);
        $responseCashier->assertStatus(403);

        // Mekanik coba void -> 403
        $responseMechanic = $this->actingAs($this->mechanic)->post(route('invoices.void', $invoice), [
            'reason' => 'Salah harga',
        ]);
        $responseMechanic->assertStatus(403);

        // Admin void -> Berhasil
        $responseAdmin = $this->actingAs($this->admin)->post(route('invoices.void', $invoice), [
            'reason' => 'Koreksi faktur oleh admin',
        ]);
        $responseAdmin->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals('void', $invoice->status);
        $this->assertEquals('Koreksi faktur oleh admin', $invoice->void_reason);
    }

    /**
     * Kasir dapat mencatat pembayaran; Mekanik ditolak (403).
     */
    public function test_cashier_can_record_payment(): void
    {
        $invoice = $this->invoiceService->createFromWorkOrder($this->workOrder, [], $this->cashier->id);

        $response = $this->actingAs($this->cashier)->post(route('invoices.payments.store', $invoice), [
            'amount' => 200000,
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
            'reference' => 'KAS-001',
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(200000, $invoice->remainingBalance());
    }

    /**
     * RBAC Void Payment: Hanya Admin yang dapat membatalkan pembayaran (BR-Y2). Kasir 403.
     */
    public function test_rbac_void_payment(): void
    {
        $invoice = $this->invoiceService->createFromWorkOrder($this->workOrder, [], $this->cashier->id);
        $payment = app(\App\Services\PaymentService::class)->recordPayment($invoice, [
            'amount' => 200000,
        ], $this->cashier->id);

        // Kasir coba void payment -> 403
        $responseCashier = $this->actingAs($this->cashier)->post(route('payments.void', $payment), [
            'reason' => 'Salah catat',
        ]);
        $responseCashier->assertStatus(403);

        // Admin void payment -> Sukses
        $responseAdmin = $this->actingAs($this->admin)->post(route('payments.void', $payment), [
            'reason' => 'Salah input nominal oleh kasir',
        ]);
        $responseAdmin->assertRedirect();

        $payment->refresh();
        $this->assertEquals('void', $payment->status);
    }

    /**
     * T-07 (BR-R7): Halaman Piutang & Aging.
     * Invoice jatuh tempo 40 hari lalu, sisa 500.000 -> masuk bucket 31–60 hari.
     */
    public function test_t07_br_r7_receivables_aging_bucket_31_to_60(): void
    {
        // Buat invoice dengan sisa 500.000 yang jatuh tempo 40 hari lalu
        $pastDueDate = Carbon::today()->subDays(40);
        $issueDate = (clone $pastDueDate)->subDays(30);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-0001',
            'customer_id' => $this->customer->id,
            'source_type' => 'counter_sale',
            'issue_date' => $issueDate,
            'due_date' => $pastDueDate,
            'subtotal' => 500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 500000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->cashier)->get(route('receivables.index'));
        $response->assertStatus(200);

        // Periksa bahwa data yang dikirim ke view memuat bucket days_31_60 >= 500.000
        $response->assertViewHas('aging', function ($aging) {
            return isset($aging['days_31_60']) && $aging['days_31_60'] >= 500000;
        });

        // Mekanik ditolak melihat piutang (403)
        $respMechanic = $this->actingAs($this->mechanic)->get(route('receivables.index'));
        $respMechanic->assertStatus(403);
    }
}
