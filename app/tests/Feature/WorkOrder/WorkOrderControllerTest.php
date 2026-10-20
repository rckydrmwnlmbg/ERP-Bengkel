<?php

namespace Tests\Feature\WorkOrder;

use App\Models\Customer;
use App\Models\EquipmentUnit;
use App\Models\Part;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $mechanic;
    protected User $cashier;
    protected Customer $customer;
    protected EquipmentUnit $unit;
    protected Part $part;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->mechanic = User::factory()->create(['role' => 'mechanic']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);

        $this->customer = Customer::create([
            'code' => 'CUST-TEST',
            'name' => 'CV Mandiri Tambang',
            'type' => 'company',
            'phone' => '08211234567',
            'payment_term_days' => 14,
        ]);

        $this->unit = EquipmentUnit::create([
            'customer_id' => $this->customer->id,
            'brand' => 'Caterpillar',
            'model' => '320D',
            'serial_number' => 'CAT320D-999',
            'unit_code' => 'EXC-CAT-01',
            'hour_meter' => 6200,
        ]);

        $this->part = Part::create([
            'sku' => 'SP-CTRL-01',
            'name' => 'Hose Return Hidrolik',
            'category' => 'Hidrolik',
            'unit' => 'Pcs',
            'sell_price' => 350000,
            'avg_cost' => 200000,
            'qty_on_hand' => 0,
            'min_stock' => 1,
            'is_active' => true,
        ]);
        app(StockService::class)->recordPurchaseIn($this->part->id, 5, 200000);

        $this->service = Service::create([
            'code' => 'JS-RAD-01',
            'name' => 'Pembersihan Radiator & Coolant Flush',
            'default_price' => 250000,
        ]);
    }

    public function test_all_roles_can_view_work_orders_index(): void
    {
        $wo = app(WorkOrderService::class)->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Overheat pada temperatur air radiator',
        ], $this->mechanic->id);

        // Admin
        $this->actingAs($this->admin)->get(route('work-orders.index'))
            ->assertStatus(200)
            ->assertSee($wo->wo_number)
            ->assertSee('CV Mandiri Tambang');

        // Mekanik
        $this->actingAs($this->mechanic)->get(route('work-orders.index'))
            ->assertStatus(200)
            ->assertSee($wo->wo_number)
            ->assertSee('CV Mandiri Tambang');

        // Kasir
        $this->actingAs($this->cashier)->get(route('work-orders.index'))
            ->assertStatus(200)
            ->assertSee($wo->wo_number)
            ->assertSee('CV Mandiri Tambang');
    }

    public function test_mechanic_can_create_work_order(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Bocoran oli swing motor',
            'diagnosis' => 'Seal aus akibat jam kerja tinggi',
            'mechanic_id' => $this->mechanic->id,
        ];

        $response = $this->actingAs($this->mechanic)->post(route('work-orders.store'), $payload);

        $this->assertDatabaseHas('work_orders', [
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Bocoran oli swing motor',
            'status' => 'open',
            'created_by' => $this->mechanic->id,
        ]);

        $wo = WorkOrder::where('complaint', 'Bocoran oli swing motor')->firstOrFail();
        $response->assertRedirect(route('work-orders.show', $wo));
        $this->assertStringStartsWith('WO-', $wo->wo_number);
    }

    public function test_cashier_cannot_create_work_order(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Unauthorized creation by cashier',
        ];

        $response = $this->actingAs($this->cashier)->post(route('work-orders.store'), $payload);
        $response->assertStatus(403);
    }

    public function test_mechanic_can_add_part_and_service_items(): void
    {
        $wo = app(WorkOrderService::class)->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Servis berkala excavator',
        ], $this->mechanic->id);

        // Tambah Jasa
        $responseSrv = $this->actingAs($this->mechanic)->post(route('work-orders.services.store', $wo), [
            'service_id' => $this->service->id,
            'qty' => 1,
        ]);
        $responseSrv->assertRedirect(route('work-orders.show', $wo));
        $this->assertDatabaseHas('work_order_items', [
            'work_order_id' => $wo->id,
            'item_type' => 'service',
            'service_id' => $this->service->id,
            'unit_price' => 250000,
        ]);

        // Tambah Sparepart (Stok berkurang dari 5 menjadi 3)
        $responsePart = $this->actingAs($this->mechanic)->post(route('work-orders.parts.store', $wo), [
            'part_id' => $this->part->id,
            'qty' => 2,
        ]);
        $responsePart->assertRedirect(route('work-orders.show', $wo));
        $this->assertDatabaseHas('work_order_items', [
            'work_order_id' => $wo->id,
            'item_type' => 'part',
            'part_id' => $this->part->id,
            'qty' => 2,
            'unit_price' => 350000,
            'unit_cost_snapshot' => 200000,
        ]);

        $this->part->refresh();
        $this->assertEquals(3, $this->part->qty_on_hand);
    }

    public function test_mechanic_can_transition_status_from_open_to_in_progress_and_completed(): void
    {
        $wo = app(WorkOrderService::class)->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Servis flushing',
        ], $this->mechanic->id);

        // 1. open -> in_progress
        $respProgress = $this->actingAs($this->mechanic)->post(route('work-orders.status', $wo), [
            'status' => 'in_progress',
        ]);
        $respProgress->assertRedirect(route('work-orders.show', $wo));
        $wo->refresh();
        $this->assertEquals('in_progress', $wo->status);

        // Tambah 1 jasa agar tidak 0 item (BR-W4)
        app(WorkOrderService::class)->addServiceItem($wo, $this->service->id, 1, $this->mechanic->id);

        // 2. in_progress -> completed
        $respComplete = $this->actingAs($this->mechanic)->post(route('work-orders.status', $wo), [
            'status' => 'completed',
        ]);
        $respComplete->assertRedirect(route('work-orders.show', $wo));
        $wo->refresh();
        $this->assertEquals('completed', $wo->status);
        $this->assertNotNull($wo->completed_at);
    }

    public function test_cost_price_is_hidden_from_mechanic_and_cashier_on_work_order_show(): void
    {
        $wo = app(WorkOrderService::class)->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Ganti selang',
        ], $this->mechanic->id);

        app(WorkOrderService::class)->addPartItem($wo, $this->part->id, 1, null, $this->mechanic->id);

        // Admin melihat Snapshot HPP
        $responseAdmin = $this->actingAs($this->admin)->get(route('work-orders.show', $wo));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Snapshot HPP (BR-S8)');
        $responseAdmin->assertSee('200.000');

        // Mekanik TIDAK melihat Snapshot HPP
        $responseMechanic = $this->actingAs($this->mechanic)->get(route('work-orders.show', $wo));
        $responseMechanic->assertStatus(200);
        $responseMechanic->assertDontSee('Snapshot HPP (BR-S8)');

        // Kasir TIDAK melihat Snapshot HPP
        $responseCashier = $this->actingAs($this->cashier)->get(route('work-orders.show', $wo));
        $responseCashier->assertStatus(200);
        $responseCashier->assertDontSee('Snapshot HPP (BR-S8)');
    }
}
