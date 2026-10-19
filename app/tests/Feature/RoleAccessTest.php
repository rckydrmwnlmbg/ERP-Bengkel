<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tamu / Guest dialihkan ke login ketika mengakses halaman terproteksi.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/cashier/direct-sales')->assertRedirect('/login');
        $this->get('/mechanic/service-orders')->assertRedirect('/login');
    }

    /**
     * Admin memiliki hak akses penuh ke seluruh rute dan Gates.
     */
    public function test_admin_has_full_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/cashier/direct-sales')->assertOk();
        $this->actingAs($admin)->get('/mechanic/service-orders')->assertOk();

        // Verifikasi Gate Admin per docs/PRD.md
        $this->assertTrue(Gate::forUser($admin)->allows('master-data.manage'));
        $this->assertTrue(Gate::forUser($admin)->allows('view-cost-price'));
        $this->assertTrue(Gate::forUser($admin)->allows('service-orders.manage'));
        $this->assertTrue(Gate::forUser($admin)->allows('direct-sales.manage'));
        $this->assertTrue(Gate::forUser($admin)->allows('invoices.manage'));
        $this->assertTrue(Gate::forUser($admin)->allows('void-financial'));
        $this->assertTrue(Gate::forUser($admin)->allows('inventory.manage'));
        $this->assertTrue(Gate::forUser($admin)->allows('expenses.manage'));
        $this->assertTrue(Gate::forUser($admin)->allows('reports.view'));
        $this->assertTrue(Gate::forUser($admin)->allows('audit-logs.view'));
        $this->assertTrue(Gate::forUser($admin)->allows('settings.manage'));
    }

    /**
     * Kasir dapat mengakses rute kasir tetapi dilarang mengakses rute admin dan mekanik.
     */
    public function test_cashier_access_permissions(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/dashboard')->assertOk();
        $this->actingAs($cashier)->get('/cashier/direct-sales')->assertOk();

        // Ditolak pada rute khusus admin dan mekanik (HTTP 403 Forbidden)
        $this->actingAs($cashier)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($cashier)->get('/mechanic/service-orders')->assertForbidden();

        // Verifikasi Gate Kasir per docs/PRD.md:
        // Kasir BISA: tambah customer, penjualan langsung, invoice & pembayaran, lihat piutang
        $this->assertTrue(Gate::forUser($cashier)->allows('customer.create'));
        $this->assertTrue(Gate::forUser($cashier)->allows('direct-sales.manage'));
        $this->assertTrue(Gate::forUser($cashier)->allows('invoices.manage'));
        $this->assertTrue(Gate::forUser($cashier)->allows('payments.manage'));
        $this->assertTrue(Gate::forUser($cashier)->allows('reports.receivables.view'));

        // Kasir TIDAK BISA: lihat harga beli/HPP, void invoice/bayar, barang masuk/stok, pengeluaran, laporan umum, audit log, settings
        $this->assertFalse(Gate::forUser($cashier)->allows('view-cost-price'));
        $this->assertFalse(Gate::forUser($cashier)->allows('void-financial'));
        $this->assertFalse(Gate::forUser($cashier)->allows('inventory.manage'));
        $this->assertFalse(Gate::forUser($cashier)->allows('expenses.manage'));
        $this->assertFalse(Gate::forUser($cashier)->allows('reports.view'));
        $this->assertFalse(Gate::forUser($cashier)->allows('audit-logs.view'));
        $this->assertFalse(Gate::forUser($cashier)->allows('settings.manage'));
        $this->assertFalse(Gate::forUser($cashier)->allows('service-orders.manage'));
    }

    /**
     * Mekanik dapat mengakses order servis tetapi dilarang mengakses rute admin dan kasir.
     */
    public function test_mechanic_access_permissions(): void
    {
        $mechanic = User::factory()->mechanic()->create();

        $this->actingAs($mechanic)->get('/dashboard')->assertOk();
        $this->actingAs($mechanic)->get('/mechanic/service-orders')->assertOk();

        // Ditolak pada rute khusus admin dan kasir (HTTP 403 Forbidden)
        $this->actingAs($mechanic)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($mechanic)->get('/cashier/direct-sales')->assertForbidden();

        // Verifikasi Gate Mekanik per docs/PRD.md:
        // Mekanik BISA: order servis, lihat master data
        $this->assertTrue(Gate::forUser($mechanic)->allows('service-orders.manage'));
        $this->assertTrue(Gate::forUser($mechanic)->allows('master-data.view'));

        // Mekanik TIDAK BISA: harga beli/HPP, penjualan langsung, invoice/bayar, barang masuk, pengeluaran, laporan, settings
        $this->assertFalse(Gate::forUser($mechanic)->allows('view-cost-price'));
        $this->assertFalse(Gate::forUser($mechanic)->allows('direct-sales.manage'));
        $this->assertFalse(Gate::forUser($mechanic)->allows('invoices.manage'));
        $this->assertFalse(Gate::forUser($mechanic)->allows('payments.manage'));
        $this->assertFalse(Gate::forUser($mechanic)->allows('inventory.manage'));
        $this->assertFalse(Gate::forUser($mechanic)->allows('expenses.manage'));
        $this->assertFalse(Gate::forUser($mechanic)->allows('reports.view'));
        $this->assertFalse(Gate::forUser($mechanic)->allows('reports.receivables.view'));
        $this->assertFalse(Gate::forUser($mechanic)->allows('audit-logs.view'));
        $this->assertFalse(Gate::forUser($mechanic)->allows('settings.manage'));
    }

    /**
     * Pengguna non-aktif ditolak aksesnya meskipun memiliki peran yang sesuai.
     */
    public function test_inactive_users_are_forbidden(): void
    {
        $inactiveAdmin = User::factory()->admin()->create(['is_active' => false]);

        $this->actingAs($inactiveAdmin)->get('/admin/dashboard')->assertForbidden();
    }

    /**
     * Banner DEMO tampil di halaman terautentikasi dan tamu.
     */
    public function test_demo_banner_is_displayed_on_ui(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('MODE DEMO');
        $response->assertSee('Semua Data Fiktif');
    }
}
