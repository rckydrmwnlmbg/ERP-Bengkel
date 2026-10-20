<?php

namespace Tests\Feature\MasterData;

use App\Models\Customer;
use App\Models\EquipmentUnit;
use App\Models\Service;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $mechanic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->cashier = User::factory()->cashier()->create();
        $this->mechanic = User::factory()->mechanic()->create();
    }

    public function test_all_roles_can_view_customer_list_and_details(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-001',
            'name' => 'PT Borneo Jaya Tambang',
            'type' => 'company',
            'phone' => '08123456789',
            'payment_term_days' => 30,
        ]);

        // Admin, Cashier, Mechanic can view index & show
        $this->actingAs($this->admin)->get('/customers')->assertOk()->assertSee('PT Borneo Jaya Tambang');
        $this->actingAs($this->cashier)->get('/customers')->assertOk()->assertSee('PT Borneo Jaya Tambang');
        $this->actingAs($this->mechanic)->get('/customers')->assertOk()->assertSee('PT Borneo Jaya Tambang');

        $this->actingAs($this->admin)->get("/customers/{$customer->id}")->assertOk()->assertSee('CUST-001');
        $this->actingAs($this->cashier)->get("/customers/{$customer->id}")->assertOk()->assertSee('CUST-001');
        $this->actingAs($this->mechanic)->get("/customers/{$customer->id}")->assertOk()->assertSee('CUST-001');
    }

    public function test_admin_and_cashier_can_create_customer(): void
    {
        // Admin create
        $responseAdmin = $this->actingAs($this->admin)->post('/customers', [
            'code' => 'CUST-ADM',
            'name' => 'PT Mandiri Sejahtera',
            'type' => 'company',
            'phone' => '0811111111',
            'address' => 'Balikpapan',
            'payment_term_days' => 14,
        ]);
        $responseAdmin->assertRedirect();
        $this->assertDatabaseHas('customers', ['code' => 'CUST-ADM', 'name' => 'PT Mandiri Sejahtera']);

        // Cashier create (PRD: Kasir: Lihat, tambah customer)
        $responseCashier = $this->actingAs($this->cashier)->post('/customers', [
            'code' => 'CUST-CSH',
            'name' => 'Bpk. Hendra Gunawan',
            'type' => 'individual',
            'phone' => '0822222222',
            'payment_term_days' => 0,
        ]);
        $responseCashier->assertRedirect();
        $this->assertDatabaseHas('customers', ['code' => 'CUST-CSH', 'name' => 'Bpk. Hendra Gunawan']);
    }

    public function test_mechanic_cannot_create_customer(): void
    {
        $response = $this->actingAs($this->mechanic)->post('/customers', [
            'code' => 'CUST-MECH',
            'name' => 'CV Mechanic Created',
            'type' => 'company',
            'payment_term_days' => 0,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('customers', ['code' => 'CUST-MECH']);
    }

    public function test_customer_validation_rules_on_store(): void
    {
        // Missing required fields
        $response = $this->actingAs($this->admin)->post('/customers', []);
        $response->assertSessionHasErrors(['code', 'name', 'type', 'payment_term_days']);

        // Duplicate code
        Customer::create([
            'code' => 'CUST-DUP',
            'name' => 'Customer Existing',
            'type' => 'individual',
            'payment_term_days' => 0,
        ]);

        $responseDuplicate = $this->actingAs($this->admin)->post('/customers', [
            'code' => 'CUST-DUP',
            'name' => 'New Customer Same Code',
            'type' => 'company',
            'payment_term_days' => 0,
        ]);
        $responseDuplicate->assertSessionHasErrors(['code']);
    }

    public function test_admin_can_update_customer_but_cashier_cannot(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-UPD',
            'name' => 'Initial Name',
            'type' => 'company',
            'payment_term_days' => 10,
        ]);

        // Cashier forbidden to update
        $this->actingAs($this->cashier)->put("/customers/{$customer->id}", [
            'code' => 'CUST-UPD',
            'name' => 'Cashier Updated Name',
            'type' => 'company',
            'payment_term_days' => 10,
        ])->assertForbidden();

        // Admin can update
        $response = $this->actingAs($this->admin)->put("/customers/{$customer->id}", [
            'code' => 'CUST-UPD',
            'name' => 'Admin Updated Name',
            'type' => 'company',
            'payment_term_days' => 45,
        ]);
        $response->assertRedirect("/customers/{$customer->id}");
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Admin Updated Name',
            'payment_term_days' => 45,
        ]);
    }

    public function test_admin_can_soft_delete_customer(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-DEL',
            'name' => 'To Be Deleted Customer',
            'type' => 'company',
            'payment_term_days' => 0,
        ]);

        // Mechanic & Cashier cannot delete
        $this->actingAs($this->mechanic)->delete("/customers/{$customer->id}")->assertForbidden();
        $this->actingAs($this->cashier)->delete("/customers/{$customer->id}")->assertForbidden();

        // Admin can soft delete
        $this->actingAs($this->admin)->delete("/customers/{$customer->id}")
            ->assertRedirect('/customers');

        // Verify soft deleted: row remains in DB with deleted_at not null
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertCount(0, Customer::all());
        $this->assertCount(1, Customer::withTrashed()->get());
    }

    public function test_equipment_unit_crud(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-EQ',
            'name' => 'PT Armada Nusantara',
            'type' => 'company',
            'payment_term_days' => 30,
        ]);

        // Store Equipment Unit
        $response = $this->actingAs($this->admin)->post("/customers/{$customer->id}/equipment-units", [
            'customer_id' => $customer->id,
            'brand' => 'Komatsu',
            'model' => 'PC200-8',
            'serial_number' => 'KMTPC200-98765',
            'unit_code' => 'EX-01',
            'hour_meter' => 4500,
            'notes' => 'Unit di site Kutai',
        ]);
        $response->assertRedirect("/customers/{$customer->id}");

        $unit = EquipmentUnit::where('unit_code', 'EX-01')->first();
        $this->assertNotNull($unit);
        $this->assertEquals('Komatsu', $unit->brand);
        $this->assertEquals(4500, $unit->hour_meter);

        // Update Equipment Unit
        $this->actingAs($this->admin)->put("/equipment-units/{$unit->id}", [
            'brand' => 'Komatsu',
            'model' => 'PC200-8MO',
            'serial_number' => 'KMTPC200-98765',
            'unit_code' => 'EX-01',
            'hour_meter' => 4850,
        ])->assertRedirect("/customers/{$customer->id}");

        $this->assertEquals(4850, $unit->fresh()->hour_meter);

        // Delete Equipment Unit
        $this->actingAs($this->admin)->delete("/equipment-units/{$unit->id}")
            ->assertRedirect("/customers/{$customer->id}");
        $this->assertDatabaseMissing('equipment_units', ['id' => $unit->id]);
    }

    /**
     * BR-P1: Harga jasa pada order = tarif khusus customer bila ada, jika tidak services.default_price.
     */
    public function test_br_p1_customer_service_rate_resolution(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-RATE',
            'name' => 'PT Maju Makmur (Tarif Khusus)',
            'type' => 'company',
            'payment_term_days' => 30,
        ]);

        $service = Service::create([
            'code' => 'SRV-01',
            'name' => 'Overhaul Pompa Hidrolik',
            'default_price' => 5000000,
        ]);

        /** @var PricingService $pricingService */
        $pricingService = app(PricingService::class);

        // Sebelum ada tarif khusus: harga mengikuti default_price
        $priceDefault = $pricingService->resolveServicePrice($customer->id, $service->id);
        $this->assertEquals(5000000, $priceDefault);

        // Pasang tarif khusus untuk customer
        $this->actingAs($this->admin)->post("/customers/{$customer->id}/rates", [
            'service_id' => $service->id,
            'price' => 4250000,
        ])->assertRedirect("/customers/{$customer->id}");

        // Setelah ada tarif khusus: harga mengikuti tarif khusus customer (BR-P1)
        $priceCustom = $pricingService->resolveServicePrice($customer->id, $service->id);
        $this->assertEquals(4250000, $priceCustom);
    }
}
