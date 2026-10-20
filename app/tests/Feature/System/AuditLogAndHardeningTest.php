<?php

namespace Tests\Feature\System;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLogAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin dapat melihat halaman audit log dan memfilter data.
     */
    public function test_admin_can_view_audit_logs_and_filter(): void
    {
        $admin = User::factory()->admin()->create();

        // Buat dummy audit log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'PRICE_UPDATE',
            'entity_type' => 'Part',
            'entity_id' => 10,
            'old_values' => ['sell_price' => 100000],
            'new_values' => ['sell_price' => 125000],
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'INVOICE_VOID',
            'entity_type' => 'Invoice',
            'entity_id' => 5,
            'old_values' => ['status' => 'issued'],
            'new_values' => ['status' => 'void'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('audit-logs.index'));
        $response->assertOk();
        $response->assertSee('Audit Log Aktivitas Sistem');
        $this->assertCount(2, $response->viewData('logs'));

        // Filter by action
        $filterResponse = $this->actingAs($admin)->get(route('audit-logs.index', ['action' => 'PRICE_UPDATE']));
        $filterResponse->assertOk();
        $this->assertCount(1, $filterResponse->viewData('logs'));
        $this->assertEquals('PRICE_UPDATE', $filterResponse->viewData('logs')->first()->action);
    }

    /**
     * Kasir dan Mekanik dilarang melihat Audit Log (403).
     */
    public function test_non_admin_cannot_access_audit_logs(): void
    {
        $cashier = User::factory()->cashier()->create();
        $mechanic = User::factory()->mechanic()->create();

        $this->actingAs($cashier)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($mechanic)->get(route('audit-logs.index'))->assertForbidden();
    }

    /**
     * Admin dapat membuka dan memperbarui pengaturan sistem, serta perubahannya tercatat di Audit Log.
     */
    public function test_admin_can_update_settings_and_audit_log_is_recorded(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Pengaturan Sistem')
            ->assertSee('Nama Bengkel')
            ->assertSee('Tarif Pajak PPN (%)');

        $updateResponse = $this->actingAs($admin)->post(route('settings.update'), [
            'shop_name' => 'Bengkel Perkasa Sejahtera',
            'tax_rate_percent' => 11,
        ]);

        $updateResponse->assertRedirect(route('settings.index'));
        $updateResponse->assertSessionHas('success');

        $this->assertEquals('Bengkel Perkasa Sejahtera', Setting::get('shop_name'));
        $this->assertEquals(11, Setting::getTaxRate());

        // Verifikasi audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SETTINGS_UPDATE',
            'entity_type' => 'Setting',
            'user_id' => $admin->id,
        ]);
    }

    /**
     * Kasir dan Mekanik dilarang mengubah pengaturan sistem.
     */
    public function test_non_admin_cannot_manage_settings(): void
    {
        $cashier = User::factory()->cashier()->create();
        $mechanic = User::factory()->mechanic()->create();

        $this->actingAs($cashier)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($cashier)->post(route('settings.update'), [
            'shop_name' => 'Hacked',
            'tax_rate_percent' => 50,
        ])->assertForbidden();

        $this->actingAs($mechanic)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($mechanic)->post(route('settings.update'), [
            'shop_name' => 'Hacked',
            'tax_rate_percent' => 50,
        ])->assertForbidden();
    }

    /**
     * Optimasi N+1: Invoice::totalPaid() tidak mengeksekusi query database jika validPayments ter-load.
     */
    public function test_invoice_total_paid_uses_eager_loaded_relation_without_extra_queries(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-001',
            'name' => 'PT Prima Heavy',
            'type' => 'company',
            'payment_term_days' => 30,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-2610-0001',
            'customer_id' => $customer->id,
            'source_type' => 'counter_sale',
            'subtotal' => 1000000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000000,
            'status' => 'partially_paid',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 400000,
            'method' => 'transfer',
            'status' => 'valid',
            'paid_at' => now(),
        ]);

        // Eager load validPayments
        $loadedInvoice = Invoice::with('validPayments')->find($invoice->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $paid = $loadedInvoice->totalPaid();
        $remaining = $loadedInvoice->remainingBalance();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals(400000, $paid);
        $this->assertEquals(600000, $remaining);
        $this->assertEquals(0, $queryCount, 'Pemanggilan totalPaid dan remainingBalance tidak boleh mengeksekusi query ekstra saat validPayments di-eager load.');
    }

    /**
     * Banner DEMO harus terlihat di layout aplikasi dan welcome page.
     */
    public function test_demo_banner_is_rendered_in_views(): void
    {
        $admin = User::factory()->admin()->create();

        config(['app.demo' => true]);

        // Welcome page
        $welcomeRes = $this->get('/');
        $welcomeRes->assertOk();
        $welcomeRes->assertSee('MODE DEMO');

        // Authenticated dashboard
        $dashRes = $this->actingAs($admin)->get(route('dashboard'));
        $dashRes->assertOk();
        $dashRes->assertSee('MODE DEMO');
    }
}
