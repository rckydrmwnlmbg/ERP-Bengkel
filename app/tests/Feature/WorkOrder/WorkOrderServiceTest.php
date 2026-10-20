<?php

namespace Tests\Feature\WorkOrder;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidWorkOrderOperationException;
use App\Models\Customer;
use App\Models\CustomerServiceRate;
use App\Models\EquipmentUnit;
use App\Models\Part;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\PricingService;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkOrderService $workOrderService;
    protected StockService $stockService;
    protected PricingService $pricingService;
    protected User $mechanic;
    protected Customer $customer;
    protected EquipmentUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workOrderService = app(WorkOrderService::class);
        $this->stockService = app(StockService::class);
        $this->pricingService = app(PricingService::class);

        $this->mechanic = User::factory()->create(['role' => 'mechanic']);

        $this->customer = Customer::create([
            'code' => 'CUST-MAJU',
            'name' => 'PT Maju Bersama Makmur',
            'type' => 'company',
            'phone' => '08123456789',
            'payment_term_days' => 30,
        ]);

        $this->unit = EquipmentUnit::create([
            'customer_id' => $this->customer->id,
            'brand' => 'Komatsu',
            'model' => 'PC200-8',
            'serial_number' => 'KMTPC200-123456',
            'unit_code' => 'EXC-01',
            'hour_meter' => 4500,
        ]);
    }

    /**
     * T-02 (BR-S2, BR-S4):
     * Stok 3, tambahkan 4 ke order -> ditolak (InsufficientStockException),
     * stok tetap 3, tidak ada movement baru.
     */
    public function test_t02_br_s2_insufficient_stock_rejection_in_work_order(): void
    {
        // Setup part dengan stok awal = 3, avg_cost = 50.000
        $part = Part::create([
            'sku' => 'SP-T02-PART',
            'name' => 'Bearing Roda 6205',
            'category' => 'Bearing',
            'unit' => 'Pcs',
            'sell_price' => 75000,
            'avg_cost' => 50000,
            'qty_on_hand' => 0,
            'min_stock' => 1,
            'is_active' => true,
        ]);

        // Masukkan stok 3 pcs lewat StockService
        $this->stockService->recordPurchaseIn($part->id, 3, 50000);
        $part->refresh();
        $this->assertEquals(3, $part->qty_on_hand);

        // Buat Work Order
        $wo = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Bunyi dengung di bearing roda',
            'mechanic_id' => $this->mechanic->id,
        ], $this->mechanic->id);

        $movementsCountBefore = StockMovement::count();

        // Tambahkan 4 ke order -> Harus dilempar InsufficientStockException
        $this->expectException(InsufficientStockException::class);

        try {
            $this->workOrderService->addPartItem($wo, $part->id, 4, null, $this->mechanic->id);
        } finally {
            // Verifikasi stok tetap 3 dan tidak ada movement baru yang tersimpan
            $part->refresh();
            $this->assertEquals(3, $part->qty_on_hand, 'Stok harus tetap 3 setelah permintaan melebihi stok ditolak.');
            $this->assertEquals($movementsCountBefore, StockMovement::count(), 'Tidak boleh ada movement baru tercatat.');
            $this->assertEquals(0, $wo->items()->count(), 'Item tidak boleh tersimpan pada order servis.');
        }
    }

    /**
     * T-03 (BR-P1, BR-P2, BR-P3, BR-S4, BR-S7):
     * Stok Filter Oli 10, avg Rp100.000, harga jual Rp150.000.
     * Jasa "Ganti Oli" standar Rp200.000, tarif PT Maju Rp175.000.
     * Order PT Maju: 2 × Filter Oli + 1 × Ganti Oli.
     * - Subtotal order = 2 × 150.000 + 175.000 = 475.000
     * - Stok Filter Oli sisa 8, avg tetap 100.000
     * - Ubah tarif Ganti Oli PT Maju jadi 190.000 setelah order dibuat -> subtotal order tetap 475.000 (BR-P2 snapshot).
     */
    public function test_t03_br_p1_p2_pricing_and_stock_snapshot_integrity(): void
    {
        // 1. Part: Filter Oli, stok awal 10 @ Rp100.000, harga jual Rp150.000
        $part = Part::create([
            'sku' => 'SP-FLT-OLI',
            'name' => 'Filter Oli Komatsu',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 150000,
            'avg_cost' => 0,
            'qty_on_hand' => 0,
            'min_stock' => 2,
            'is_active' => true,
        ]);
        $this->stockService->recordPurchaseIn($part->id, 10, 100000);
        $part->refresh();
        $this->assertEquals(10, $part->qty_on_hand);
        $this->assertEquals(100000, $part->avg_cost);

        // 2. Service: Ganti Oli standar Rp200.000
        $service = Service::create([
            'code' => 'JS-GNT-OLI',
            'name' => 'Ganti Oli Mesin',
            'default_price' => 200000,
        ]);

        // Tarif khusus PT Maju: Rp175.000 (BR-P1)
        CustomerServiceRate::create([
            'customer_id' => $this->customer->id,
            'service_id' => $service->id,
            'price' => 175000,
        ]);

        // 3. Buat order servis untuk PT Maju
        $wo = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Jadwal servis berkala ganti oli dan filter',
            'mechanic_id' => $this->mechanic->id,
        ], $this->mechanic->id);

        // Tambah 2 x Filter Oli
        $partItem = $this->workOrderService->addPartItem($wo, $part->id, 2, null, $this->mechanic->id);

        // Tambah 1 x Ganti Oli Mesin
        $serviceItem = $this->workOrderService->addServiceItem($wo, $service->id, 1, $this->mechanic->id);

        // 4. Verifikasi Subtotal order = 2 * 150.000 + 175.000 = 475.000
        $this->assertEquals(300000, $partItem->line_total);
        $this->assertEquals(175000, $serviceItem->line_total);
        $this->assertEquals(475000, $wo->calculateTotal());

        // 5. Verifikasi stok Filter Oli sisa 8, avg tetap 100.000 (BR-S7)
        $part->refresh();
        $this->assertEquals(8, $part->qty_on_hand);
        $this->assertEquals(100000, $part->avg_cost);

        // Verifikasi unit_cost_snapshot tersimpan pada item
        $this->assertEquals(100000, $partItem->unit_cost_snapshot);

        // 6. Ubah tarif Ganti Oli PT Maju jadi 190.000 (BR-P2)
        $rate = CustomerServiceRate::where('customer_id', $this->customer->id)
            ->where('service_id', $service->id)
            ->firstOrFail();
        $rate->update(['price' => 190000]);

        // Ubah juga default price service master jadi 250.000
        $service->update(['default_price' => 250000]);

        // Subtotal order servis HARUS TETAP 475.000 (Snapshot tidak boleh terpengaruh)
        $wo->refresh();
        $this->assertEquals(475000, $wo->calculateTotal(), 'Subtotal order harus tetap 475.000 sesuai snapshot harga (BR-P2).');
    }

    /**
     * BR-S4: Hapus sparepart dari order servis mengembalikan stok (SERVICE_RETURN).
     */
    public function test_br_s4_removing_part_item_returns_stock(): void
    {
        $part = Part::create([
            'sku' => 'SP-RET-TEST',
            'name' => 'Seal Kit Boom',
            'category' => 'Seal',
            'unit' => 'Set',
            'sell_price' => 450000,
            'avg_cost' => 0,
            'qty_on_hand' => 0,
            'min_stock' => 1,
            'is_active' => true,
        ]);
        $this->stockService->recordPurchaseIn($part->id, 5, 300000);

        $wo = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Bocor hidrolik',
        ], $this->mechanic->id);

        // Ambil 2 pcs untuk WO
        $item = $this->workOrderService->addPartItem($wo, $part->id, 2, null, $this->mechanic->id);
        $part->refresh();
        $this->assertEquals(3, $part->qty_on_hand);

        // Hapus item dari WO
        $this->workOrderService->removeItem($wo, $item->id, $this->mechanic->id);

        // Stok harus kembali menjadi 5
        $part->refresh();
        $this->assertEquals(5, $part->qty_on_hand);

        // Verifikasi movement SERVICE_RETURN tercatat
        $this->assertDatabaseHas('stock_movements', [
            'part_id' => $part->id,
            'type' => 'SERVICE_RETURN',
            'qty_change' => 2,
            'unit_cost_snapshot' => 300000,
            'balance_after' => 5,
        ]);
    }

    /**
     * BR-W1 & BR-W4: Alur status order servis dan pencegahan penyelesaian order tanpa item.
     */
    public function test_br_w1_and_br_w4_status_workflow(): void
    {
        $wo = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Pemeriksaan rem',
        ], $this->mechanic->id);

        $this->assertEquals('open', $wo->status);

        // 1. Transisi ilegal: open langsung ke completed harus ditolak (harus in_progress dulu)
        $this->expectException(InvalidWorkOrderOperationException::class);
        $this->workOrderService->transitionStatus($wo, 'completed');
    }

    /**
     * BR-W4: Order tanpa item tidak bisa diselesaikan (completed).
     */
    public function test_br_w4_order_without_items_cannot_be_completed(): void
    {
        $wo = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Pemeriksaan radiator',
        ], $this->mechanic->id);

        // open -> in_progress (Legal)
        $wo = $this->workOrderService->transitionStatus($wo, 'in_progress');
        $this->assertEquals('in_progress', $wo->status);

        // in_progress -> completed tanpa item HARUS DITOLAK (BR-W4)
        $this->expectException(InvalidWorkOrderOperationException::class);
        $this->expectExceptionMessage('Order servis tanpa item pekerjaan atau sparepart tidak dapat diselesaikan (BR-W4).');

        $this->workOrderService->transitionStatus($wo, 'completed');
    }

    /**
     * BR-W1 & BR-S4: Pembatalan order servis mengembalikan semua sparepart ke stok.
     */
    public function test_cancelling_work_order_restores_all_parts(): void
    {
        $part = Part::create([
            'sku' => 'SP-CAN-01',
            'name' => 'Van Belt Komatsu',
            'category' => 'Belt',
            'unit' => 'Pcs',
            'sell_price' => 120000,
            'avg_cost' => 0,
            'qty_on_hand' => 0,
            'min_stock' => 1,
            'is_active' => true,
        ]);
        $this->stockService->recordPurchaseIn($part->id, 4, 80000);

        $wo = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Ganti van belt putus',
        ], $this->mechanic->id);

        $this->workOrderService->addPartItem($wo, $part->id, 2, null, $this->mechanic->id);
        $part->refresh();
        $this->assertEquals(2, $part->qty_on_hand);

        // Batalkan order servis
        $this->workOrderService->transitionStatus($wo, 'cancelled', $this->mechanic->id);
        $wo->refresh();
        $this->assertEquals('cancelled', $wo->status);

        // Stok sparepart harus kembali utuh menjadi 4 (BR-S4)
        $part->refresh();
        $this->assertEquals(4, $part->qty_on_hand);
    }

    /**
     * BR-W2: Item tidak bisa ditambah atau dihapus saat status completed / cancelled.
     */
    public function test_br_w2_items_cannot_be_modified_when_completed(): void
    {
        $service = Service::create([
            'code' => 'JS-STL-KLP',
            'name' => 'Stel Klep Mesin',
            'default_price' => 300000,
        ]);

        $wo = $this->workOrderService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $this->unit->id,
            'complaint' => 'Bunyi klep kasar',
        ], $this->mechanic->id);

        $wo = $this->workOrderService->transitionStatus($wo, 'in_progress');
        $item = $this->workOrderService->addServiceItem($wo, $service->id, 1, $this->mechanic->id);

        // Selesaikan order
        $wo = $this->workOrderService->transitionStatus($wo, 'completed');
        $this->assertEquals('completed', $wo->status);

        // Coba tambah item baru saat completed -> Harus Ditolak (BR-W2)
        $this->expectException(InvalidWorkOrderOperationException::class);
        $this->workOrderService->addServiceItem($wo, $service->id, 1);
    }
}
