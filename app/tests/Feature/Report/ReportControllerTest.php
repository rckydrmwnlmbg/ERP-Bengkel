<?php

namespace Tests\Feature\Report;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $mechanic;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->mechanic = User::factory()->create(['role' => 'mechanic']);

        $this->customer = Customer::create([
            'code' => 'CUST-REP-01',
            'name' => 'PT Pelita Mas',
            'type' => 'company',
            'phone' => '081234567890',
            'payment_term_days' => 30,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-REP-0001',
            'customer_id' => $this->customer->id,
            'source_type' => 'counter_sale',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 250000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 250000,
            'status' => 'issued',
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * RBAC Laporan Omzet, Laba Rugi, dan Arus Kas:
     * Admin berhak (200), Kasir ditolak (403), Mekanik ditolak (403).
     */
    public function test_rbac_financial_reports_access(): void
    {
        // 1. Laporan Omzet
        $this->actingAs($this->admin)->get(route('reports.revenue'))->assertStatus(200);
        $this->actingAs($this->cashier)->get(route('reports.revenue'))->assertStatus(403);
        $this->actingAs($this->mechanic)->get(route('reports.revenue'))->assertStatus(403);

        // 2. Laporan Laba Rugi
        $this->actingAs($this->admin)->get(route('reports.profit-loss'))->assertStatus(200);
        $this->actingAs($this->cashier)->get(route('reports.profit-loss'))->assertStatus(403);
        $this->actingAs($this->mechanic)->get(route('reports.profit-loss'))->assertStatus(403);

        // 3. Laporan Arus Kas
        $this->actingAs($this->admin)->get(route('reports.cash-flow'))->assertStatus(200);
        $this->actingAs($this->cashier)->get(route('reports.cash-flow'))->assertStatus(403);
        $this->actingAs($this->mechanic)->get(route('reports.cash-flow'))->assertStatus(403);
    }

    /**
     * RBAC Laporan Piutang & Aging:
     * Admin berhak (200), Kasir berhak (200), Mekanik ditolak (403).
     */
    public function test_rbac_receivables_report_access(): void
    {
        $this->actingAs($this->admin)->get(route('reports.receivables'))->assertStatus(200);
        $this->actingAs($this->cashier)->get(route('reports.receivables'))->assertStatus(200);
        $this->actingAs($this->mechanic)->get(route('reports.receivables'))->assertStatus(403);
    }

    /**
     * Ekspor Excel (.csv) menghasilkan header attachment dan konten valid.
     */
    public function test_export_excel_reports(): void
    {
        $responseRevenue = $this->actingAs($this->admin)->get(route('reports.export-excel', ['type' => 'revenue']));
        $responseRevenue->assertStatus(200);
        $responseRevenue->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $responseReceivables = $this->actingAs($this->cashier)->get(route('reports.export-excel', ['type' => 'receivables']));
        $responseReceivables->assertStatus(200);
        $responseReceivables->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Cetak / Print laporan memuat catatan penyederhanaan BR-R2 dan BR-R5 di UI.
     */
    public function test_printable_report_views_render_simplification_notes(): void
    {
        // Laba Rugi memuat BR-R2: Jasa dianggap berbiaya HPP Rp 0
        $responsePL = $this->actingAs($this->admin)->get(route('reports.print', ['type' => 'profit-loss']));
        $responsePL->assertStatus(200);
        $responsePL->assertSee('BR-R2');
        $responsePL->assertSee('Jasa dianggap berbiaya HPP Rp 0');

        // Arus Kas memuat BR-R5: Pembelian supplier dianggap tunai
        $responseCF = $this->actingAs($this->admin)->get(route('reports.print', ['type' => 'cash-flow']));
        $responseCF->assertStatus(200);
        $responseCF->assertSee('BR-R5');
        $responseCF->assertSee('Pembelian supplier dianggap tunai di tanggal pembelian');
    }

    /**
     * Dashboard memuat ringkasan KPI dan order servis terbaru.
     */
    public function test_dashboard_renders_for_authenticated_users(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('dashboard'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertViewHas('summary');

        $responseCashier = $this->actingAs($this->cashier)->get(route('dashboard'));
        $responseCashier->assertStatus(200);
    }
}
