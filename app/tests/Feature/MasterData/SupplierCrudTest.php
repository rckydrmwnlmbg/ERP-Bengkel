<?php

namespace Tests\Feature\MasterData;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
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

    public function test_all_roles_can_view_suppliers(): void
    {
        Supplier::create([
            'name' => 'PT United Filter Utama',
            'phone' => '021-5551234',
            'address' => 'Jakarta Timur',
        ]);

        $this->actingAs($this->admin)->get('/suppliers')->assertOk()->assertSee('PT United Filter Utama');
        $this->actingAs($this->cashier)->get('/suppliers')->assertOk()->assertSee('PT United Filter Utama');
        $this->actingAs($this->mechanic)->get('/suppliers')->assertOk()->assertSee('PT United Filter Utama');
    }

    public function test_admin_can_create_update_and_delete_supplier(): void
    {
        // Store
        $this->actingAs($this->admin)->post('/suppliers', [
            'name' => 'CV Sumber Sparepart Jaya',
            'phone' => '0811998877',
            'address' => 'Balikpapan Utara',
        ])->assertRedirect('/suppliers');

        $supplier = Supplier::where('name', 'CV Sumber Sparepart Jaya')->first();
        $this->assertNotNull($supplier);

        // Update
        $this->actingAs($this->admin)->put("/suppliers/{$supplier->id}", [
            'name' => 'CV Sumber Sparepart Jaya Abadi',
            'phone' => '0811998877',
            'address' => 'Balikpapan Baru',
        ])->assertRedirect('/suppliers');

        $this->assertEquals('CV Sumber Sparepart Jaya Abadi', $supplier->fresh()->name);

        // Delete
        $this->actingAs($this->admin)->delete("/suppliers/{$supplier->id}")
            ->assertRedirect('/suppliers');
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_cashier_and_mechanic_cannot_manage_suppliers(): void
    {
        $supplier = Supplier::create([
            'name' => 'PT Supplier Protected',
            'phone' => '021-123456',
        ]);

        // Cashier
        $this->actingAs($this->cashier)->post('/suppliers', [
            'name' => 'New Supplier',
        ])->assertForbidden();

        $this->actingAs($this->cashier)->put("/suppliers/{$supplier->id}", [
            'name' => 'Updated by Cashier',
        ])->assertForbidden();

        $this->actingAs($this->cashier)->delete("/suppliers/{$supplier->id}")->assertForbidden();

        // Mechanic
        $this->actingAs($this->mechanic)->post('/suppliers', [
            'name' => 'New Supplier',
        ])->assertForbidden();

        $this->actingAs($this->mechanic)->put("/suppliers/{$supplier->id}", [
            'name' => 'Updated by Mechanic',
        ])->assertForbidden();

        $this->actingAs($this->mechanic)->delete("/suppliers/{$supplier->id}")->assertForbidden();
    }
}
