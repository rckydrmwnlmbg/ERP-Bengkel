<?php

namespace Tests\Feature\Stock;

use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartStockCardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private User $mechanic;
    private Part $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->mechanic = User::factory()->create(['role' => 'mechanic']);

        $this->part = Part::create([
            'sku' => 'KRT-STK-01',
            'name' => 'Hydraulic Hose 1/2 inch 200 Bar',
            'category' => 'Hidrolik',
            'unit' => 'Meter',
            'sell_price' => 175000,
            'avg_cost' => 110000,
            'qty_on_hand' => 15,
            'min_stock' => 5,
            'is_active' => true,
        ]);

        StockMovement::create([
            'part_id' => $this->part->id,
            'type' => 'PURCHASE_IN',
            'qty_change' => 20,
            'unit_cost_snapshot' => 110000,
            'balance_after' => 20,
            'ref_type' => 'purchase',
            'ref_id' => 1,
            'reason' => 'Pembelian batch 1',
            'created_by' => $this->admin->id,
            'created_at' => now()->subDays(2),
        ]);

        StockMovement::create([
            'part_id' => $this->part->id,
            'type' => 'SERVICE_OUT',
            'qty_change' => -5,
            'unit_cost_snapshot' => 110000,
            'balance_after' => 15,
            'ref_type' => 'work_order',
            'ref_id' => 10,
            'reason' => 'Pemakaian servis SPK #10',
            'created_by' => $this->admin->id,
            'created_at' => now()->subDay(),
        ]);
    }

    public function test_admin_can_view_stock_card_with_hpp_and_unit_costs(): void
    {
        $response = $this->actingAs($this->admin)->get(route('parts.stock-card', $this->part));

        $response->assertStatus(200);
        $response->assertSee('Kartu Stok');
        $response->assertSee('Hydraulic Hose 1/2 inch 200 Bar');
        $response->assertSee('Barang Masuk (Beli)');
        $response->assertSee('Pemakaian Servis');

        // Admin berhak melihat HPP / Snapshot HPP
        $response->assertSee('HPP Rata-rata (WAC)');
        $response->assertSee('Snapshot HPP (BR-S8)');
        $response->assertSee('110.000');
    }

    public function test_cashier_and_mechanic_can_view_stock_card_without_hpp(): void
    {
        // Kasir
        $responseCashier = $this->actingAs($this->cashier)->get(route('parts.stock-card', $this->part));
        $responseCashier->assertStatus(200);
        $responseCashier->assertSee('Hydraulic Hose 1/2 inch 200 Bar');
        $responseCashier->assertDontSee('HPP Rata-rata (WAC)');
        $responseCashier->assertDontSee('Snapshot HPP (BR-S8)');

        // Mekanik
        $responseMechanic = $this->actingAs($this->mechanic)->get(route('parts.stock-card', $this->part));
        $responseMechanic->assertStatus(200);
        $responseMechanic->assertSee('Hydraulic Hose 1/2 inch 200 Bar');
        $responseMechanic->assertDontSee('HPP Rata-rata (WAC)');
        $responseMechanic->assertDontSee('Snapshot HPP (BR-S8)');
    }

    public function test_stock_card_displays_invariant_validation_status(): void
    {
        $response = $this->actingAs($this->admin)->get(route('parts.stock-card', $this->part));

        $response->assertStatus(200);
        // Validasi matematis BR-S1
        $response->assertSee('Status Integritas Stok (BR-S1)');
        $response->assertSee('Integritas Valid');
    }
}
