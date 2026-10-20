<?php

namespace Tests\Feature\Stock;

use App\Models\Part;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private User $mechanic;
    private Supplier $supplier;
    private Part $partA;
    private Part $partB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->mechanic = User::factory()->create(['role' => 'mechanic']);

        $this->supplier = Supplier::create([
            'name' => 'PT Sumber Makmur Partindo',
            'contact_person' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Industri No. 10 Jakarta',
            'is_active' => true,
        ]);

        $this->partA = Part::create([
            'sku' => 'PUR-PART-01',
            'name' => 'Oli Mesin HD 15W-40 20L',
            'category' => 'Pelumas',
            'unit' => 'Pail',
            'sell_price' => 1200000,
            'avg_cost' => 800000,
            'qty_on_hand' => 5,
            'min_stock' => 2,
            'is_active' => true,
        ]);

        $this->partB = Part::create([
            'sku' => 'PUR-PART-02',
            'name' => 'Filter Solar Komatsu PC200',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 250000,
            'avg_cost' => 150000,
            'qty_on_hand' => 10,
            'min_stock' => 4,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_purchases_index(): void
    {
        Purchase::create([
            'reference' => 'SJ-SUPP-001',
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'total' => 5000000,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('purchases.index'));

        $response->assertStatus(200);
        $response->assertSee('SJ-SUPP-001');
        $response->assertSee('PT Sumber Makmur Partindo');
    }

    public function test_admin_can_view_purchase_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('purchases.create'));

        $response->assertStatus(200);
        $response->assertSee('Catat Barang Masuk');
        $response->assertSee('PT Sumber Makmur Partindo');
    }

    public function test_admin_can_store_purchase_with_items_and_stock_is_updated(): void
    {
        $payload = [
            'reference' => 'SJ-2026-X1',
            'supplier_id' => $this->supplier->id,
            'purchase_date' => '2026-10-20',
            'notes' => 'Pembelian batch perdana',
            'items' => [
                [
                    'part_id' => $this->partA->id,
                    'qty' => 5,
                    'unit_cost' => 900000,
                ],
                [
                    'part_id' => $this->partB->id,
                    'qty' => 10,
                    'unit_cost' => 160000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('purchases.store'), $payload);

        $purchase = Purchase::where('reference', 'SJ-2026-X1')->firstOrFail();
        $response->assertRedirect(route('purchases.show', $purchase));
        $response->assertSessionHas('success');

        // Total: (5 * 900,000) + (10 * 160,000) = 4,500,000 + 1,600,000 = 6,100,000
        $this->assertDatabaseHas('purchases', [
            'reference' => 'SJ-2026-X1',
            'supplier_id' => $this->supplier->id,
            'total' => 6100000,
            'created_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'part_id' => $this->partA->id,
            'qty' => 5,
            'unit_cost' => 900000,
        ]);

        // Verifikasi stok partA bertambah: 5 + 5 = 10
        // WAC partA: (5 * 800,000 + 5 * 900,000) / 10 = 850,000
        $this->partA->refresh();
        $this->assertEquals(10, $this->partA->qty_on_hand);
        $this->assertEquals(850000, $this->partA->avg_cost);

        // Verifikasi stok partB bertambah: 10 + 10 = 20
        // WAC partB: (10 * 150,000 + 10 * 160,000) / 20 = 155,000
        $this->partB->refresh();
        $this->assertEquals(20, $this->partB->qty_on_hand);
        $this->assertEquals(155000, $this->partB->avg_cost);

        // Verifikasi stock movements tercatat immutable
        $this->assertDatabaseHas('stock_movements', [
            'part_id' => $this->partA->id,
            'type' => 'PURCHASE_IN',
            'qty_change' => 5,
            'unit_cost_snapshot' => 900000,
            'balance_after' => 10,
            'ref_type' => 'Purchase',
        ]);
    }

    public function test_purchase_store_validation_rejects_empty_items(): void
    {
        $payload = [
            'invoice_number' => 'INV-EMPTY',
            'supplier_id' => $this->supplier->id,
            'purchase_date' => '2026-10-20',
            'items' => [],
        ];

        $response = $this->actingAs($this->admin)->post(route('purchases.store'), $payload);

        $response->assertSessionHasErrors(['items']);
    }

    public function test_cashier_and_mechanic_are_forbidden_from_purchases(): void
    {
        $responseCashier = $this->actingAs($this->cashier)->get(route('purchases.index'));
        $responseCashier->assertStatus(403);

        $responseMechanic = $this->actingAs($this->mechanic)->get(route('purchases.create'));
        $responseMechanic->assertStatus(403);

        $responseStore = $this->actingAs($this->cashier)->post(route('purchases.store'), [
            'reference' => 'SJ-HACK',
            'supplier_id' => $this->supplier->id,
            'purchase_date' => '2026-10-20',
            'items' => [
                ['part_id' => $this->partA->id, 'qty' => 1, 'unit_cost' => 1000],
            ],
        ]);
        $responseStore->assertStatus(403);
    }
}
