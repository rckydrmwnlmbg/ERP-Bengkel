<?php

namespace Tests\Feature\MasterData;

use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartCrudTest extends TestCase
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

    public function test_admin_can_create_part_with_zero_initial_stock(): void
    {
        // Tanpa pergerakan stok dulu: qty_on_hand harus 0 saat part dibuat
        $response = $this->actingAs($this->admin)->post('/parts', [
            'sku' => 'SP-FL-001',
            'name' => 'Filter Oli Mesin PC200',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 150000,
            'min_stock' => 5,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/parts');
        $part = Part::where('sku', 'SP-FL-001')->first();

        $this->assertNotNull($part);
        $this->assertEquals('Filter Oli Mesin PC200', $part->name);
        $this->assertEquals(150000, $part->sell_price);
        $this->assertEquals(0, $part->qty_on_hand, 'Stok awal harus 0 di Phase 2 per instruksi PRD.');
        $this->assertEquals(0, $part->avg_cost);
    }

    public function test_cashier_and_mechanic_cannot_create_update_or_delete_part(): void
    {
        $part = Part::create([
            'sku' => 'SP-TEST-01',
            'name' => 'Baut Track Shoe',
            'category' => 'Undercarriage',
            'unit' => 'Pcs',
            'sell_price' => 25000,
            'min_stock' => 20,
        ]);

        // Cashier attempts
        $this->actingAs($this->cashier)->post('/parts', [
            'sku' => 'SP-CSH',
            'name' => 'Cashier Part',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 100000,
            'min_stock' => 5,
        ])->assertForbidden();

        $this->actingAs($this->cashier)->put("/parts/{$part->id}", [
            'sku' => 'SP-TEST-01',
            'name' => 'Updated by Cashier',
            'category' => 'Undercarriage',
            'unit' => 'Pcs',
            'sell_price' => 30000,
            'min_stock' => 20,
        ])->assertForbidden();

        $this->actingAs($this->cashier)->delete("/parts/{$part->id}")->assertForbidden();

        // Mechanic attempts
        $this->actingAs($this->mechanic)->post('/parts', [
            'sku' => 'SP-MEC',
            'name' => 'Mechanic Part',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 100000,
            'min_stock' => 5,
        ])->assertForbidden();

        $this->actingAs($this->mechanic)->put("/parts/{$part->id}", [
            'sku' => 'SP-TEST-01',
            'name' => 'Updated by Mechanic',
            'category' => 'Undercarriage',
            'unit' => 'Pcs',
            'sell_price' => 30000,
            'min_stock' => 20,
        ])->assertForbidden();

        $this->actingAs($this->mechanic)->delete("/parts/{$part->id}")->assertForbidden();
    }

    public function test_part_validation_rules_on_store(): void
    {
        // Required fields
        $this->actingAs($this->admin)->post('/parts', [])
            ->assertSessionHasErrors(['sku', 'name', 'category', 'unit', 'sell_price', 'min_stock']);

        // Duplicate SKU
        Part::create([
            'sku' => 'SP-DUP',
            'name' => 'Existing Part',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 50000,
            'min_stock' => 1,
        ]);

        $this->actingAs($this->admin)->post('/parts', [
            'sku' => 'SP-DUP',
            'name' => 'New Part Same SKU',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 50000,
            'min_stock' => 1,
        ])->assertSessionHasErrors(['sku']);
    }

    public function test_admin_can_update_and_soft_delete_part(): void
    {
        $part = Part::create([
            'sku' => 'SP-DEL',
            'name' => 'Part to Soft Delete',
            'category' => 'Seal & Gasket',
            'unit' => 'Set',
            'sell_price' => 75000,
            'min_stock' => 2,
        ]);

        // Update
        $this->actingAs($this->admin)->put("/parts/{$part->id}", [
            'sku' => 'SP-DEL',
            'name' => 'Part Updated Name',
            'category' => 'Seal & Gasket',
            'unit' => 'Set',
            'sell_price' => 85000,
            'min_stock' => 4,
            'is_active' => 1,
        ])->assertRedirect('/parts');

        $this->assertEquals(85000, $part->fresh()->sell_price);

        // Soft Delete
        $this->actingAs($this->admin)->delete("/parts/{$part->id}")->assertRedirect('/parts');
        $this->assertSoftDeleted('parts', ['id' => $part->id]);
        $this->assertCount(0, Part::all());
        $this->assertCount(1, Part::withTrashed()->get());
    }

    /**
     * BR-P4: Harga jual tidak boleh lebih rendah dari harga pokok rata-rata saat dibuat;
     * admin boleh override dengan alasan (tercatat di audit log).
     */
    public function test_br_p4_sell_price_cannot_be_lower_than_avg_cost_without_override_reason(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRP4',
            'name' => 'Filter Solar Atas',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 120000,
            'avg_cost' => 100000, // HPP = 100.000
            'min_stock' => 5,
        ]);

        // Coba turunkan harga jual ke Rp80.000 (di bawah HPP 100.000) tanpa alasan override
        $response = $this->actingAs($this->admin)->put("/parts/{$part->id}", [
            'sku' => 'SP-BRP4',
            'name' => 'Filter Solar Atas',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 80000,
            'min_stock' => 5,
            'is_active' => 1,
            'override_reason' => '', // Kosong
        ]);

        $response->assertSessionHasErrors(['sell_price']);
        $this->assertEquals(120000, $part->fresh()->sell_price);
    }

    /**
     * BR-P4: Admin boleh override harga jual < avg_cost jika menyertakan alasan.
     */
    public function test_br_p4_sell_price_lower_than_avg_cost_allowed_with_admin_override_reason(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRP4-OK',
            'name' => 'Filter Solar Bawah',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 150000,
            'avg_cost' => 110000, // HPP = 110.000
            'min_stock' => 5,
        ]);

        // Turunkan harga jual ke Rp95.000 dengan alasan override
        $response = $this->actingAs($this->admin)->put("/parts/{$part->id}", [
            'sku' => 'SP-BRP4-OK',
            'name' => 'Filter Solar Bawah',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 95000,
            'min_stock' => 5,
            'is_active' => 1,
            'override_reason' => 'Obral cuci gudang stok lambat bergerak',
        ]);

        $response->assertRedirect('/parts');
        $this->assertEquals(95000, $part->fresh()->sell_price);

        // Verifikasi audit log mencatat override_reason
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Part',
            'entity_id' => $part->id,
            'action' => 'price_change',
        ]);
    }
}
