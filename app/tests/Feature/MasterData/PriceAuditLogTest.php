<?php

namespace Tests\Feature\MasterData;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_part_sell_price_change_writes_audit_log(): void
    {
        $part = Part::create([
            'sku' => 'SP-AUDIT-1',
            'name' => 'Track Link Assembly',
            'category' => 'Undercarriage',
            'unit' => 'Set',
            'sell_price' => 7500000,
            'avg_cost' => 5000000,
            'min_stock' => 1,
        ]);

        // Update harga jual menjadi 8.000.000
        $this->actingAs($this->admin)->put("/parts/{$part->id}", [
            'sku' => 'SP-AUDIT-1',
            'name' => 'Track Link Assembly',
            'category' => 'Undercarriage',
            'unit' => 'Set',
            'sell_price' => 8000000,
            'min_stock' => 1,
            'is_active' => 1,
        ])->assertRedirect('/parts');

        $log = AuditLog::where('entity_type', 'Part')
            ->where('entity_id', $part->id)
            ->where('action', 'price_change')
            ->first();

        $this->assertNotNull($log, 'Audit log perubahan harga jual sparepart harus dibuat.');
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals(7500000, $log->old_values['sell_price']);
        $this->assertEquals(8000000, $log->new_values['sell_price']);
    }

    public function test_service_default_price_change_writes_audit_log(): void
    {
        $service = Service::create([
            'code' => 'SRV-AUDIT-1',
            'name' => 'Jasa Kunjungan Mekanik Lapangan',
            'default_price' => 1500000,
        ]);

        // Update tarif standar jasa menjadi 1.750.000
        $this->actingAs($this->admin)->put("/services/{$service->id}", [
            'code' => 'SRV-AUDIT-1',
            'name' => 'Jasa Kunjungan Mekanik Lapangan',
            'default_price' => 1750000,
            'is_active' => 1,
        ])->assertRedirect('/services');

        $log = AuditLog::where('entity_type', 'Service')
            ->where('entity_id', $service->id)
            ->where('action', 'price_change')
            ->first();

        $this->assertNotNull($log, 'Audit log perubahan tarif jasa standar harus dibuat.');
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals(1500000, $log->old_values['default_price']);
        $this->assertEquals(1750000, $log->new_values['default_price']);
    }

    public function test_customer_service_rate_change_writes_audit_log(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-AUDIT',
            'name' => 'PT Mitra Sarana',
            'type' => 'company',
            'payment_term_days' => 30,
        ]);

        $service = Service::create([
            'code' => 'SRV-RATE-AUDIT',
            'name' => 'Ganti Track Shoe',
            'default_price' => 2500000,
        ]);

        // Pasang tarif khusus baru: 2.200.000
        $this->actingAs($this->admin)->post("/customers/{$customer->id}/rates", [
            'service_id' => $service->id,
            'price' => 2200000,
        ])->assertRedirect("/customers/{$customer->id}");

        $logCreate = AuditLog::where('entity_type', 'CustomerServiceRate')
            ->where('action', 'rate_created')
            ->first();

        $this->assertNotNull($logCreate, 'Audit log pembuatan tarif khusus customer harus tercatat.');
        $this->assertEquals(2200000, $logCreate->new_values['price']);

        // Update tarif khusus menjadi 2.100.000
        $this->actingAs($this->admin)->post("/customers/{$customer->id}/rates", [
            'service_id' => $service->id,
            'price' => 2100000,
        ])->assertRedirect("/customers/{$customer->id}");

        $logChange = AuditLog::where('entity_type', 'CustomerServiceRate')
            ->where('action', 'price_change')
            ->first();

        $this->assertNotNull($logChange, 'Audit log pembaruan tarif khusus customer harus tercatat.');
        $this->assertEquals(2200000, $logChange->old_values['price']);
        $this->assertEquals(2100000, $logChange->new_values['price']);
    }
}
