<?php

namespace Tests\Feature;

use App\Exceptions\InvalidInvoiceOperationException;
use App\Models\Customer;
use App\Models\EquipmentUnit;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\InvoiceService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderInvoiceButtonTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $mechanic;
    protected Customer $customer;
    protected EquipmentUnit $unit;
    protected Part $part;
    protected WorkOrder $completedWorkOrder;
    protected InvoiceService $invoiceService;
    protected WorkOrderService $woService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = app(InvoiceService::class);
        $this->woService = app(WorkOrderService::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->mechanic = User::factory()->create(['role' => 'mechanic']);

        $this->customer = Customer::create([
            'code' => 'CUST-TEST-01',
            'name' => 'PT Prima Tambang Sejahtera',
            'type' => 'company',
            'phone' => '081234567890',
            'payment_term_days' => 14,
        ]);

        $this->unit = EquipmentUnit::create([
            'customer_id' => $this->customer->id,
            'brand' => 'Komatsu',
            'model' => 'PC200-8',
            'serial_number' => 'KM-PC200-8812',
            'unit_code' => 'EXC-01',
            'hour_meter' => 4200,
        ]);

        $this->part = Part::create([
            'sku' => 'PRT-FLT-01',
            'name' => 'Oil Filter PC200',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 250000,
            'avg_cost' => 150000,
            'qty_on_hand' => 10,
            'min_stock' => 2,
            'is_active' => true,
        ]);

        StockMovement::create([
            'part_id' => $this->part->id,
            'type' => 'PURCHASE_IN',
            'qty_change' => 10,
            'unit_cost_snapshot' => 150000,
            'balance_after' => 10,
            'reason' => 'Stok awal test',
            'created_by' => $this->admin->id,
        ]);

        $this->completedWorkOrder = $this->createWorkOrderWithStatus('completed');
    }

    private function createWorkOrderWithStatus(string $status): WorkOrder
    {
        $wo = $this->woService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Service berkala 250 jam',
        ], $this->admin->id);

        $this->woService->addPartItem($wo, $this->part->id, 1, null, $this->admin->id);

        if ($status === 'in_progress') {
            $this->woService->transitionStatus($wo, 'in_progress', $this->admin->id);
        } elseif ($status === 'completed') {
            $this->woService->transitionStatus($wo, 'in_progress', $this->admin->id);
            $this->woService->transitionStatus($wo, 'completed', $this->admin->id);
        } elseif ($status === 'cancelled') {
            $this->woService->transitionStatus($wo, 'cancelled', $this->admin->id);
        }

        return $wo->fresh();
    }

    /**
     * 1. Tombol "Terbitkan Invoice" muncul untuk admin pada order servis berstatus completed.
     */
    public function test_button_appears_for_admin_on_completed_work_order(): void
    {
        $response = $this->actingAs($this->admin)->get(route('work-orders.show', $this->completedWorkOrder));

        $response->assertStatus(200);
        $response->assertSee('Terbitkan Invoice');
        $response->assertSee(route('invoices.store-from-wo', $this->completedWorkOrder));
    }

    /**
     * 2. Tombol "Terbitkan Invoice" muncul untuk kasir pada order servis berstatus completed.
     */
    public function test_button_appears_for_cashier_on_completed_work_order(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('work-orders.show', $this->completedWorkOrder));

        $response->assertStatus(200);
        $response->assertSee('Terbitkan Invoice');
        $response->assertSee(route('invoices.store-from-wo', $this->completedWorkOrder));
    }

    /**
     * 3. Tombol "Terbitkan Invoice" TIDAK muncul untuk mekanik pada order servis berstatus completed.
     */
    public function test_button_does_not_appear_for_mechanic_on_completed_work_order(): void
    {
        $response = $this->actingAs($this->mechanic)->get(route('work-orders.show', $this->completedWorkOrder));

        $response->assertStatus(200);
        $response->assertDontSee('Terbitkan Invoice');
        $response->assertDontSee(route('invoices.store-from-wo', $this->completedWorkOrder));
    }

    /**
     * 4. Tombol "Terbitkan Invoice" TIDAK muncul pada status lain (open, in_progress, cancelled).
     */
    public function test_button_does_not_appear_for_non_completed_statuses(): void
    {
        foreach (['open', 'in_progress', 'cancelled'] as $status) {
            $wo = $this->createWorkOrderWithStatus($status);

            // Tes untuk admin
            $responseAdmin = $this->actingAs($this->admin)->get(route('work-orders.show', $wo));
            $responseAdmin->assertStatus(200);
            $responseAdmin->assertDontSee('Terbitkan Invoice');

            // Tes untuk kasir
            $responseCashier = $this->actingAs($this->cashier)->get(route('work-orders.show', $wo));
            $responseCashier->assertStatus(200);
            $responseCashier->assertDontSee('Terbitkan Invoice');
        }
    }

    /**
     * 5. Jika invoice aktif sudah ada, tombol tidak tampil dan tautan ke invoice ditampilkan.
     */
    public function test_when_active_invoice_already_exists_button_does_not_appear_and_link_to_invoice_is_displayed(): void
    {
        $invoice = $this->invoiceService->createFromWorkOrder($this->completedWorkOrder, [], $this->admin->id);

        $this->completedWorkOrder->refresh();

        // Cek admin
        $responseAdmin = $this->actingAs($this->admin)->get(route('work-orders.show', $this->completedWorkOrder));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertDontSee('Terbitkan Invoice');
        $responseAdmin->assertSee(route('invoices.show', $invoice));
        $responseAdmin->assertSee($invoice->invoice_number);

        // Cek kasir
        $responseCashier = $this->actingAs($this->cashier)->get(route('work-orders.show', $this->completedWorkOrder));
        $responseCashier->assertStatus(200);
        $responseCashier->assertDontSee('Terbitkan Invoice');
        $responseCashier->assertSee(route('invoices.show', $invoice));
        $responseCashier->assertSee($invoice->invoice_number);
    }

    /**
     * 6. POST ke rute invoices/from-work-order/{id} menerbitkan invoice dan mengarahkan ke halaman invoice dengan pesan sukses.
     */
    public function test_post_terbitkan_invoice_creates_invoice_and_redirects_with_success_message(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('invoices.store-from-wo', $this->completedWorkOrder));

        $invoice = Invoice::where('work_order_id', $this->completedWorkOrder->id)->first();
        $this->assertNotNull($invoice);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success', "Invoice {$invoice->invoice_number} berhasil diterbitkan.");

        // Follow redirect dan pastikan pesan sukses tampil di halaman invoice
        $followResponse = $this->actingAs($this->cashier)->get(route('invoices.show', $invoice));
        $followResponse->assertStatus(200);
        $followResponse->assertSee("Invoice {$invoice->invoice_number} berhasil diterbitkan.");
    }

    /**
     * 7. Satu order tidak bisa memiliki dua invoice aktif (BR-W3).
     */
    public function test_work_order_cannot_have_two_active_invoices_br_w3(): void
    {
        // Terbitkan invoice pertama
        $invoice1 = $this->invoiceService->createFromWorkOrder($this->completedWorkOrder, [], $this->admin->id);
        $this->assertNotNull($invoice1);

        // Upaya menerbitkan invoice kedua lewat POST form harus ditolak
        $response = $this->actingAs($this->cashier)->post(route('invoices.store-from-wo', $this->completedWorkOrder));

        $response->assertSessionHasErrors(['error']);
        $this->assertEquals(1, Invoice::where('work_order_id', $this->completedWorkOrder->id)->count());

        // Uji langsung pada invoiceService bahwa pengecualian BR-W3 dilempar jika sudah ada invoice
        try {
            $this->invoiceService->createFromWorkOrder($this->completedWorkOrder, [], $this->admin->id);
            $this->fail('Harusnya melempar InvalidInvoiceOperationException untuk BR-W3');
        } catch (InvalidInvoiceOperationException $e) {
            $this->assertStringContainsString('BR-W3', $e->getMessage());
        }

        // Uji juga kondisi di mana status work order completed tapi active invoice sudah ada (BR-W3)
        WorkOrder::where('id', $this->completedWorkOrder->id)->update(['status' => 'completed']);
        $this->completedWorkOrder->refresh();

        try {
            $this->invoiceService->createFromWorkOrder($this->completedWorkOrder, [], $this->admin->id);
            $this->fail('Harusnya melempar InvalidInvoiceOperationException untuk BR-W3 saat active invoice ada');
        } catch (InvalidInvoiceOperationException $e) {
            $this->assertStringContainsString('Order servis sudah memiliki invoice aktif yang belum dibatalkan (BR-W3).', $e->getMessage());
        }
    }
}
