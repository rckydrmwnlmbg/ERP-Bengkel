<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EquipmentUnit;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationAndValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected Customer $customer;
    protected Part $part;
    protected WorkOrder $completedWorkOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);

        $this->customer = Customer::create([
            'code' => 'CUST-LOCALE-01',
            'name' => 'PT Tambang Maju Bersama',
            'type' => 'company',
            'phone' => '081234567899',
            'payment_term_days' => 14,
        ]);

        $this->part = Part::create([
            'sku' => 'PRT-LOC-01',
            'name' => 'Filter Udara Donut',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 200000,
            'avg_cost' => 120000,
            'qty_on_hand' => 10,
            'min_stock' => 2,
            'is_active' => true,
        ]);

        StockMovement::create([
            'part_id' => $this->part->id,
            'type' => 'PURCHASE_IN',
            'qty_change' => 10,
            'unit_cost_snapshot' => 120000,
            'balance_after' => 10,
            'reason' => 'Stok awal test',
            'created_by' => $this->admin->id,
        ]);

        $unit = EquipmentUnit::create([
            'customer_id' => $this->customer->id,
            'brand' => 'Komatsu',
            'model' => 'PC200-8',
            'serial_number' => 'KM-PC200-LOC',
            'unit_code' => 'EXC-LOC-01',
            'hour_meter' => 1200,
        ]);

        $woService = app(WorkOrderService::class);
        $wo = $woService->createWorkOrder([
            'customer_id' => $this->customer->id,
            'equipment_unit_id' => $unit->id,
            'complaint' => 'Uji validasi diskon negatif',
        ], $this->admin->id);

        $woService->addPartItem($wo, $this->part->id, 1, null, $this->admin->id);
        $woService->transitionStatus($wo, 'in_progress', $this->admin->id);
        $woService->transitionStatus($wo, 'completed', $this->admin->id);
        $this->completedWorkOrder = $wo->fresh();
    }

    /**
     * Pastikan konfigurasi lokal aplikasi aktif adalah 'id' dan fallback 'id'.
     */
    public function test_app_locale_is_configured_to_indonesian(): void
    {
        $this->assertSame('id', app()->getLocale());
        $this->assertSame('id', config('app.fallback_locale'));
    }

    /**
     * 1. Login salah: memicu kegagalan autentikasi/validasi, memastikan pesan Bahasa Indonesia,
     * tidak mengandung raw key `auth.failed` atau `\w+\.\w+\.\w+`, dan pesan tampil tepat 1 kali.
     */
    public function test_failed_login_validation_localization_and_single_display(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'pengguna.salah@bengkel.com',
            'password' => 'kata-sandi-keliru',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        /** @var \Illuminate\Support\ViewErrorBag $errorsBag */
        $errorsBag = session('errors');
        $errorMessage = $errorsBag->first('email');

        // Tidak boleh berupa raw key seperti auth.failed
        $this->assertNotEquals('auth.failed', $errorMessage);
        $this->assertDoesNotMatchRegularExpression('/\b\w+\.\w+\.\w+\b/', $errorMessage);
        $this->assertStringContainsString('Kredensial yang diberikan tidak cocok', $errorMessage);

        // Render view login dengan error bag dan pastikan pesan hanya tampil 1 kali pada halaman
        $content = view('auth.login', ['errors' => $errorsBag])->render();
        $this->assertSame(1, substr_count($content, $errorMessage), "Pesan error login harus muncul tepat 1 kali di halaman");
        $this->assertStringNotContainsString('auth.failed', $content);
    }

    /**
     * 2. Diskon negatif: memicu validasi diskon_amount < 0, memastikan pesan dalam Bahasa Indonesia,
     * tidak mengandung raw key seperti `validation.min.numeric` atau `\w+\.\w+\.\w+`, dan pesan tampil tepat 1 kali.
     */
    public function test_negative_discount_validation_localization_and_single_display(): void
    {
        $targetUrl = route('invoices.create-from-wo', $this->completedWorkOrder);

        $response = $this->actingAs($this->cashier)
            ->from($targetUrl)
            ->post(route('invoices.store-from-wo', $this->completedWorkOrder), [
                'discount_amount' => -15000,
            ]);

        $response->assertRedirect($targetUrl);
        $response->assertSessionHasErrors('discount_amount');

        /** @var \Illuminate\Support\ViewErrorBag $errorsBag */
        $errorsBag = session('errors');
        $errorMessage = $errorsBag->first('discount_amount');
        $this->assertNotEmpty($errorMessage);

        // Tidak boleh ada key mentah seperti validation.min.numeric atau pattern \w+\.\w+\.\w+
        $this->assertStringNotContainsString('validation.', $errorMessage);
        $this->assertDoesNotMatchRegularExpression('/\b\w+\.\w+\.\w+\b/', $errorMessage);

        // Render view dengan error bag dan periksa kemunculan pesan tepat 1 kali
        view()->share('errors', $errorsBag);
        $content = view('invoices.create_from_wo', [
            'workOrder' => $this->completedWorkOrder,
            'taxRate' => 0,
        ])->render();
        $this->assertSame(1, substr_count($content, $errorMessage), "Pesan error diskon negatif harus muncul tepat 1 kali di halaman");
        $this->assertStringNotContainsString('validation.min.numeric', $content);
    }

    /**
     * 3. Qty 0: memicu validasi kuantitas item = 0 pada penjualan langsung,
     * memastikan pesan dalam Bahasa Indonesia, tidak ada key mentah, dan pesan tampil tepat 1 kali.
     */
    public function test_zero_qty_validation_localization_and_single_display(): void
    {
        $targetUrl = route('direct-sales.create');

        $response = $this->actingAs($this->cashier)
            ->from($targetUrl)
            ->post(route('direct-sales.store'), [
                'customer_id' => $this->customer->id,
                'issue_date' => now()->toDateString(),
                'items' => [
                    [
                        'part_id' => $this->part->id,
                        'qty' => 0,
                    ],
                ],
            ]);

        $response->assertRedirect($targetUrl);
        $response->assertSessionHasErrors('items.0.qty');

        /** @var \Illuminate\Support\ViewErrorBag $errorsBag */
        $errorsBag = session('errors');
        $errorMessage = $errorsBag->first('items.0.qty');
        $this->assertNotEmpty($errorMessage);

        // Tidak boleh ada key mentah seperti validation.min.numeric atau pattern \w+\.\w+\.\w+
        $this->assertStringNotContainsString('validation.', $errorMessage);
        $this->assertDoesNotMatchRegularExpression('/\b\w+\.\w+\.\w+\b/', $errorMessage);

        // Render view formulir dengan error bag dan periksa kemunculan pesan tepat 1 kali
        view()->share('errors', $errorsBag);
        $content = view('invoices.create_direct_sale', [
            'customers' => [$this->customer],
            'defaultCustomer' => $this->customer,
            'parts' => [$this->part],
            'taxRate' => 0,
        ])->render();
        $this->assertSame(1, substr_count($content, $errorMessage), "Pesan error kuantitas 0 harus muncul tepat 1 kali di halaman");
        $this->assertStringNotContainsString('validation.min.numeric', $content);
    }

    /**
     * 4. Tes gabungan komprehensif sesuai instruksi: memicu validasi gagal (diskon negatif, qty 0, login salah)
     * dan memastikan seluruh pesan kesalahan terlokalisasi tanpa pola `\w+\.\w+\.\w+` mentah serta tampil satu kali.
     */
    public function test_combined_validation_failures_localization_and_no_duplicate_error(): void
    {
        // A. Login salah
        $resLogin = $this->from('/login')->post('/login', [
            'email' => 'tidakada@bengkel.com',
            'password' => 'passwordsalah',
        ]);
        $resLogin->assertRedirect('/login');
        $resLogin->assertSessionHasErrors('email');
        /** @var \Illuminate\Support\ViewErrorBag $loginBag */
        $loginBag = session('errors');
        $loginErr = $loginBag->first('email');
        $this->assertDoesNotMatchRegularExpression('/\b\w+\.\w+\.\w+\b/', $loginErr);
        $loginHtml = view('auth.login', ['errors' => $loginBag])->render();
        $this->assertSame(1, substr_count($loginHtml, $loginErr));

        // B. Diskon negatif (Validator bawaan Laravel untuk menguji lang/id/validation.php secara langsung)
        $validatorDiscount = Validator::make(
            ['discount_amount' => -25000],
            ['discount_amount' => ['required', 'integer', 'min:0']]
        );
        $this->assertTrue($validatorDiscount->fails());
        $discountErr = $validatorDiscount->errors()->first('discount_amount');
        $this->assertDoesNotMatchRegularExpression('/\b\w+\.\w+\.\w+\b/', $discountErr);
        $this->assertStringContainsString('minimal bernilai 0', $discountErr);

        // C. Qty 0 (Validator bawaan Laravel untuk menguji lang/id/validation.php secara langsung)
        $validatorQty = Validator::make(
            ['qty' => 0],
            ['qty' => ['required', 'integer', 'min:1']]
        );
        $this->assertTrue($validatorQty->fails());
        $qtyErr = $validatorQty->errors()->first('qty');
        $this->assertDoesNotMatchRegularExpression('/\b\w+\.\w+\.\w+\b/', $qtyErr);
        $this->assertStringContainsString('minimal bernilai 1', $qtyErr);
    }

    /**
     * 5. Pastikan teks "90 Hari -->" tidak bocor ke output HTML pada laporan piutang.
     */
    public function test_reports_receivables_no_html_comment_leak(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.receivables'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Tidak boleh ada kebocoran komentar HTML "90 Hari -->"
        $this->assertStringNotContainsString('90 Hari -->', $content);
        $this->assertStringNotContainsString('<!-- >', $content);

        // Teks aging "> 90 Hari" yang sah harus tampil
        $this->assertStringContainsString('&gt; 90 Hari (Macet)', $content);
    }

    /**
     * 6. Pastikan "Forgot password" dan "Remember me" dinonaktifkan di halaman login pada mode DEMO.
     */
    public function test_login_page_disables_forgot_password_and_remember_me_in_demo(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        // Remember me tidak boleh muncul
        $response->assertDontSee('name="remember"', false);
        $response->assertDontSee('Remember me');
        $response->assertDontSee('Ingat saya');

        // Forgot password tidak boleh muncul
        $response->assertDontSee(route('password.request'), false);
        $response->assertDontSee('Forgot your password?');
        $response->assertDontSee('Lupa kata sandi Anda?');
    }
}
