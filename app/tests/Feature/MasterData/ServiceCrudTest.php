<?php

namespace Tests\Feature\MasterData;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
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

    public function test_all_roles_can_view_services(): void
    {
        Service::create([
            'code' => 'SRV-TEST',
            'name' => 'Ganti Oli & Filter Mesin',
            'default_price' => 350000,
        ]);

        $this->actingAs($this->admin)->get('/services')->assertOk()->assertSee('Ganti Oli & Filter Mesin');
        $this->actingAs($this->cashier)->get('/services')->assertOk()->assertSee('Ganti Oli & Filter Mesin');
        $this->actingAs($this->mechanic)->get('/services')->assertOk()->assertSee('Ganti Oli & Filter Mesin');
    }

    public function test_admin_can_create_update_and_delete_service(): void
    {
        // Store
        $this->actingAs($this->admin)->post('/services', [
            'code' => 'SRV-OH',
            'name' => 'Overhaul Mesin Komatsu SAA6D107E',
            'default_price' => 12500000,
            'is_active' => 1,
        ])->assertRedirect('/services');

        $service = Service::where('code', 'SRV-OH')->first();
        $this->assertNotNull($service);
        $this->assertEquals(12500000, $service->default_price);

        // Update
        $this->actingAs($this->admin)->put("/services/{$service->id}", [
            'code' => 'SRV-OH',
            'name' => 'Overhaul Mesin Komatsu SAA6D107E-1',
            'default_price' => 13500000,
            'is_active' => 1,
        ])->assertRedirect('/services');

        $this->assertEquals(13500000, $service->fresh()->default_price);

        // Delete
        $this->actingAs($this->admin)->delete("/services/{$service->id}")
            ->assertRedirect('/services');
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_cashier_and_mechanic_cannot_manage_services(): void
    {
        $service = Service::create([
            'code' => 'SRV-PROTECT',
            'name' => 'Kalibrasi Control Valve',
            'default_price' => 2000000,
        ]);

        // Cashier
        $this->actingAs($this->cashier)->post('/services', [
            'code' => 'SRV-NEW',
            'name' => 'Service New',
            'default_price' => 100000,
        ])->assertForbidden();

        $this->actingAs($this->cashier)->put("/services/{$service->id}", [
            'code' => 'SRV-PROTECT',
            'name' => 'Updated by Cashier',
            'default_price' => 2500000,
        ])->assertForbidden();

        $this->actingAs($this->cashier)->delete("/services/{$service->id}")->assertForbidden();

        // Mechanic
        $this->actingAs($this->mechanic)->post('/services', [
            'code' => 'SRV-NEW',
            'name' => 'Service New',
            'default_price' => 100000,
        ])->assertForbidden();

        $this->actingAs($this->mechanic)->put("/services/{$service->id}", [
            'code' => 'SRV-PROTECT',
            'name' => 'Updated by Mechanic',
            'default_price' => 2500000,
        ])->assertForbidden();

        $this->actingAs($this->mechanic)->delete("/services/{$service->id}")->assertForbidden();
    }

    public function test_service_validation_rules(): void
    {
        $this->actingAs($this->admin)->post('/services', [])
            ->assertSessionHasErrors(['code', 'name', 'default_price']);

        Service::create([
            'code' => 'SRV-EXISTS',
            'name' => 'Service Existing',
            'default_price' => 500000,
        ]);

        $this->actingAs($this->admin)->post('/services', [
            'code' => 'SRV-EXISTS',
            'name' => 'Service Duplicated',
            'default_price' => 500000,
        ])->assertSessionHasErrors(['code']);
    }
}
