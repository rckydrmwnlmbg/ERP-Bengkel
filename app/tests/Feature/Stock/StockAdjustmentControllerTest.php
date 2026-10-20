<?php

namespace Tests\Feature\Stock;

use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentControllerTest extends TestCase
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
            'sku' => 'ADJ-PART-01',
            'name' => 'Baut Roda Excavator M20',
            'category' => 'Baut & Mur',
            'unit' => 'Pcs',
            'sell_price' => 35000,
            'avg_cost' => 20000,
            'qty_on_hand' => 10,
            'min_stock' => 5,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_adjustments_index_and_create(): void
    {
        $responseIndex = $this->actingAs($this->admin)->get(route('stock-adjustments.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Penyesuaian Stok (Stok Opname)');

        $responseCreate = $this->actingAs($this->admin)->get(route('stock-adjustments.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Form Penyesuaian Stok (Stok Opname)');
    }

    public function test_admin_can_record_positive_stock_adjustment_with_mandatory_reason(): void
    {
        $payload = [
            'part_id' => $this->part->id,
            'type' => 'ADJUSTMENT_IN',
            'qty' => 5,
            'reason' => 'Hasil stock opname akhir bulan gudang A ditemukan lebih 5 pcs',
        ];

        $response = $this->actingAs($this->admin)->post(route('stock-adjustments.store'), $payload);

        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('success');

        $this->part->refresh();
        $this->assertEquals(15, $this->part->qty_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'part_id' => $this->part->id,
            'type' => 'ADJUSTMENT_IN',
            'qty_change' => 5,
            'balance_after' => 15,
            'reason' => 'Penyesuaian Stok: Hasil stock opname akhir bulan gudang A ditemukan lebih 5 pcs',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_record_negative_stock_adjustment(): void
    {
        $payload = [
            'part_id' => $this->part->id,
            'type' => 'ADJUSTMENT_OUT',
            'qty' => 3,
            'reason' => 'Baut cacat berkarat diafkir',
        ];

        $response = $this->actingAs($this->admin)->post(route('stock-adjustments.store'), $payload);

        $response->assertRedirect(route('stock-adjustments.index'));
        $this->part->refresh();
        $this->assertEquals(7, $this->part->qty_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'part_id' => $this->part->id,
            'type' => 'ADJUSTMENT_OUT',
            'qty_change' => -3,
            'balance_after' => 7,
            'reason' => 'Penyesuaian Stok: Baut cacat berkarat diafkir',
        ]);
    }

    public function test_stock_adjustment_fails_without_reason_or_zero_qty(): void
    {
        // Alasan wajib (BR-S6)
        $responseNoReason = $this->actingAs($this->admin)->post(route('stock-adjustments.store'), [
            'part_id' => $this->part->id,
            'type' => 'ADJUSTMENT_IN',
            'qty' => 2,
            'reason' => '',
        ]);
        $responseNoReason->assertSessionHasErrors(['reason']);

        // Kuantitas 0 dilarang
        $responseZeroQty = $this->actingAs($this->admin)->post(route('stock-adjustments.store'), [
            'part_id' => $this->part->id,
            'type' => 'ADJUSTMENT_IN',
            'qty' => 0,
            'reason' => 'Tidak ada perubahan',
        ]);
        $responseZeroQty->assertSessionHasErrors(['qty']);
    }

    public function test_stock_adjustment_cannot_make_stock_negative(): void
    {
        $payload = [
            'part_id' => $this->part->id,
            'type' => 'ADJUSTMENT_OUT',
            'qty' => 11, // Stok hanya 10
            'reason' => 'Koreksi salah hitung drastis',
        ];

        $response = $this->actingAs($this->admin)->post(route('stock-adjustments.store'), $payload);

        // Harus dialihkan kembali dengan pesan error stok tidak mencukupi (BR-S2)
        $response->assertSessionHasErrors(['qty']);
        $this->part->refresh();
        $this->assertEquals(10, $this->part->qty_on_hand);
    }

    public function test_cashier_and_mechanic_are_forbidden_from_adjustments(): void
    {
        $this->actingAs($this->cashier)->get(route('stock-adjustments.index'))->assertStatus(403);
        $this->actingAs($this->mechanic)->get(route('stock-adjustments.create'))->assertStatus(403);
        $this->actingAs($this->cashier)->post(route('stock-adjustments.store'), [
            'part_id' => $this->part->id,
            'qty_change' => 1,
            'reason' => 'Unauthorized adjustment',
        ])->assertStatus(403);
    }
}
