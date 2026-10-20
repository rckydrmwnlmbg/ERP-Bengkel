<?php

namespace Tests\Feature\Stock;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockMovementException;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $stockService;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(StockService::class);
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * T-01 (BR-S7)
     * Part "Filter Oli": stok 10, avg Rp100.000.
     * Barang masuk 10 @ Rp120.000 -> stok 20, avg Rp110.000.
     * Barang masuk 1 @ Rp95.000 -> avg = round((20×110.000 + 95.000)/21) = Rp109.286.
     */
    public function test_t01_br_s7_weighted_average_cost_calculation(): void
    {
        $part = Part::create([
            'sku' => 'SP-T01',
            'name' => 'Filter Oli',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 150000,
            'avg_cost' => 0,
            'qty_on_hand' => 0,
            'min_stock' => 5,
        ]);

        // Kondisi awal: stok 10, avg Rp100.000
        $this->stockService->recordPurchaseIn($part->id, 10, 100000);
        $part->refresh();
        $this->assertEquals(10, $part->qty_on_hand);
        $this->assertEquals(100000, $part->avg_cost);

        // Langkah 1: Barang masuk 10 @ Rp120.000 -> stok 20, avg Rp110.000
        // Hitungan: (10 * 100.000 + 10 * 120.000) / 20 = 2.200.000 / 20 = 110.000
        $this->stockService->recordPurchaseIn($part->id, 10, 120000);
        $part->refresh();
        $this->assertEquals(20, $part->qty_on_hand);
        $this->assertEquals(110000, $part->avg_cost);

        // Langkah 2: Barang masuk 1 @ Rp95.000 -> stok 21, avg = round((20×110.000 + 95.000)/21) = Rp109.286
        // Hitungan: (2.200.000 + 95.000) / 21 = 2.295.000 / 21 = 109.285,71... -> round half-up = 109.286
        $this->stockService->recordPurchaseIn($part->id, 1, 95000);
        $part->refresh();
        $this->assertEquals(21, $part->qty_on_hand);
        $this->assertEquals(109286, $part->avg_cost, 'Perhitungan avg cost pada T-01 harus bernilai tepat Rp109.286.');
    }

    /**
     * T-02 (BR-S2)
     * Stok 3, tambahkan 4 ke order -> ditolak, stok tetap 3, tidak ada movement baru.
     */
    public function test_t02_br_s2_insufficient_stock_rejection(): void
    {
        $part = Part::create([
            'sku' => 'SP-T02',
            'name' => 'Busi Pemanas (Glow Plug)',
            'category' => 'Kelistrikan',
            'unit' => 'Pcs',
            'sell_price' => 125000,
            'avg_cost' => 0,
            'qty_on_hand' => 0,
            'min_stock' => 2,
        ]);

        // Masukkan stok 3 unit
        $this->stockService->recordPurchaseIn($part->id, 3, 80000);
        $part->refresh();
        $this->assertEquals(3, $part->qty_on_hand);
        $initialMovementsCount = StockMovement::where('part_id', $part->id)->count();

        // Coba keluarkan 4 unit untuk order servis -> wajib ditolak (InsufficientStockException)
        $exceptionThrown = false;
        try {
            $this->stockService->recordServiceOut($part->id, 4, 101);
        } catch (InsufficientStockException $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('BR-S2', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Pengambilan stok melebihi saldo wajib melempar InsufficientStockException.');

        // Pastikan stok tetap 3 dan tidak ada movement baru
        $part->refresh();
        $this->assertEquals(3, $part->qty_on_hand);
        $this->assertEquals($initialMovementsCount, StockMovement::where('part_id', $part->id)->count());
    }

    /**
     * T-08 (BR-S8)
     * Dua request paralel mengambil stok terakhir (stok 1): satu sukses, satu ditolak, stok akhir 0.
     * Menguji akses paralel nyata (real concurrency) menggunakan proses terpisah dan row lock.
     */
    public function test_t08_br_s8_parallel_row_lock_concurrency(): void
    {
        $tempDb = '/tmp/t08_concurrent_' . uniqid() . '.sqlite';
        @touch($tempDb);

        // Setup koneksi terpisah pada file sqlite khusus
        config(['database.connections.t08_sqlite' => [
            'driver' => 'sqlite',
            'database' => $tempDb,
            'prefix' => '',
            'busy_timeout' => 5000,
        ]]);
        DB::purge('t08_sqlite');

        // Jalankan migrasi pada database pengujian konkurensi
        Artisan::call('migrate', [
            '--database' => 't08_sqlite',
            '--force' => true,
        ]);

        DB::connection('t08_sqlite')->statement('PRAGMA journal_mode=WAL;');
        DB::connection('t08_sqlite')->statement('PRAGMA busy_timeout=10000;');

        // Buat part dengan stok = 1
        $partId = DB::connection('t08_sqlite')->table('parts')->insertGetId([
            'sku' => 'SP-T08-CONCUR',
            'name' => 'Seal O-Ring Terakhir',
            'category' => 'Seal',
            'unit' => 'Pcs',
            'sell_price' => 50000,
            'avg_cost' => 30000,
            'qty_on_hand' => 1,
            'min_stock' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Catat saldo awal 1 di stock movements
        DB::connection('t08_sqlite')->table('stock_movements')->insert([
            'part_id' => $partId,
            'type' => 'PURCHASE_IN',
            'qty_change' => 1,
            'unit_cost_snapshot' => 30000,
            'balance_after' => 1,
            'reason' => 'Stok awal',
            'created_at' => now(),
        ]);

        // Buat worker PHP script yang menjalankan pengurangan stok 1 unit dengan simulasi delay lock
        $workerScript = '/tmp/t08_worker_' . uniqid() . '.php';
        $scriptContent = <<<'WORKER'
<?php
$basePath = $argv[4];
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dbPath = $argv[1];
$partId = (int) $argv[2];
$workerId = $argv[3];

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $dbPath);
config(['database.connections.sqlite.database' => $dbPath]);
config(['database.connections.sqlite.busy_timeout' => 10000]);
\Illuminate\Support\Facades\DB::purge('sqlite');
\Illuminate\Support\Facades\DB::statement('PRAGMA journal_mode=WAL;');
\Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout=10000;');

$stockService = app(\App\Services\StockService::class);

$maxRetries = 10;
for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
    try {
        $stockService->recordServiceOut($partId, 1);
        file_put_contents("/tmp/t08_res_{$workerId}.txt", "SUCCESS");
        exit(0);
    } catch (\App\Exceptions\InsufficientStockException $e) {
        file_put_contents("/tmp/t08_res_{$workerId}.txt", "REJECTED");
        exit(2);
    } catch (\Illuminate\Database\QueryException $e) {
        if ($attempt < $maxRetries && str_contains($e->getMessage(), 'database is locked')) {
            usleep(rand(50000, 150000));
            continue;
        }
        file_put_contents("/tmp/t08_res_{$workerId}.txt", "ERROR: " . $e->getMessage());
        exit(1);
    } catch (\Throwable $e) {
        file_put_contents("/tmp/t08_res_{$workerId}.txt", "ERROR: " . $e->getMessage());
        exit(1);
    }
}
WORKER;
        file_put_contents($workerScript, $scriptContent);

        $logA = "/tmp/t08_res_workerA.txt";
        $logB = "/tmp/t08_res_workerB.txt";
        @unlink($logA);
        @unlink($logB);

        $appPath = base_path();
        $cmdA = "php {$workerScript} '{$tempDb}' {$partId} workerA '{$appPath}'";
        $cmdB = "php {$workerScript} '{$tempDb}' {$partId} workerB '{$appPath}'";

        $procA = proc_open($cmdA, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipesA, '/root/ERP/app');
        $procB = proc_open($cmdB, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipesB, '/root/ERP/app');

        $outA = stream_get_contents($pipesA[1]);
        $errA = stream_get_contents($pipesA[2]);
        $outB = stream_get_contents($pipesB[1]);
        $errB = stream_get_contents($pipesB[2]);

        $exitCodeA = proc_close($procA);
        $exitCodeB = proc_close($procB);

        $resA = file_exists($logA) ? trim(file_get_contents($logA)) : "MISSING_A (Exit: {$exitCodeA}, Err: {$errA}, Out: {$outA})";
        $resB = file_exists($logB) ? trim(file_get_contents($logB)) : "MISSING_B (Exit: {$exitCodeB}, Err: {$errB}, Out: {$outB})";

        // Bersihkan file temporer
        @unlink($workerScript);
        @unlink($tempDb);
        @unlink($logA);
        @unlink($logB);

        $results = [$resA, $resB];
        // Salah satu proses harus sukses mengambil stok 1 terakhir, dan yang satu lagi harus ditolak
        $this->assertContains('SUCCESS', $results, 'Satu request harus sukses mengambil stok terakhir. Actual: ' . json_encode($results));
        $this->assertContains('REJECTED', $results, 'Satu request harus ditolak karena stok habis (BR-S8). Results: ' . json_encode($results));

        // Jumlah sukses tepat 1 dan jumlah ditolak tepat 1
        $successCount = count(array_filter($results, fn ($r) => $r === 'SUCCESS'));
        $rejectedCount = count(array_filter($results, fn ($r) => $r === 'REJECTED'));
        $this->assertEquals(1, $successCount);
        $this->assertEquals(1, $rejectedCount);
    }

    /**
     * T-09 (BR-S1)
     * Setelah rangkaian operasi acak 200 langkah (masuk, keluar, retur),
     * qty_on_hand = Σ movements untuk setiap part.
     */
    public function test_t09_br_s1_random_200_steps_operations_integrity(): void
    {
        $partA = Part::create([
            'sku' => 'SP-RND-A',
            'name' => 'Filter Solar Random A',
            'category' => 'Filter',
            'unit' => 'Pcs',
            'sell_price' => 100000,
            'avg_cost' => 0,
            'qty_on_hand' => 0,
            'min_stock' => 5,
        ]);

        $partB = Part::create([
            'sku' => 'SP-RND-B',
            'name' => 'Bearing Roda Random B',
            'category' => 'Undercarriage',
            'unit' => 'Pcs',
            'sell_price' => 200000,
            'avg_cost' => 0,
            'qty_on_hand' => 0,
            'min_stock' => 5,
        ]);

        $parts = [$partA, $partB];
        mt_srand(42); // Seed konstan agar deterministik

        for ($i = 0; $i < 200; $i++) {
            /** @var Part $currentPart */
            $currentPart = $parts[$i % 2];
            $currentPart->refresh();

            $action = mt_rand(1, 4);

            switch ($action) {
                case 1: // Pembelian masuk
                    $inQty = mt_rand(1, 15);
                    $inCost = mt_rand(50000, 150000);
                    $this->stockService->recordPurchaseIn($currentPart->id, $inQty, $inCost);
                    break;

                case 2: // Pemakaian servis keluar
                    $outQty = mt_rand(1, 10);
                    if ($currentPart->qty_on_hand >= $outQty) {
                        $this->stockService->recordServiceOut($currentPart->id, $outQty, $i + 1);
                    }
                    break;

                case 3: // Penjualan langsung keluar
                    $saleQty = mt_rand(1, 8);
                    if ($currentPart->qty_on_hand >= $saleQty) {
                        $this->stockService->recordSaleOut($currentPart->id, $saleQty, $i + 1);
                    }
                    break;

                case 4: // Penyesuaian opname (tambah atau kurang)
                    if (mt_rand(0, 1) === 1) {
                        $adjQty = mt_rand(1, 5);
                        $this->stockService->recordAdjustment($currentPart->id, 'ADJUSTMENT_IN', $adjQty, "Opname random {$i}");
                    } else {
                        $adjQty = mt_rand(1, 3);
                        if ($currentPart->qty_on_hand >= $adjQty) {
                            $this->stockService->recordAdjustment($currentPart->id, 'ADJUSTMENT_OUT', $adjQty, "Opname random {$i}");
                        }
                    }
                    break;
            }
        }

        // Verifikasi integritas BR-S1 untuk setiap part
        foreach ($parts as $p) {
            $p->refresh();
            $sumMovements = (int) StockMovement::where('part_id', $p->id)->sum('qty_change');
            $this->assertEquals(
                $p->qty_on_hand,
                $sumMovements,
                "Invarian BR-S1 gagal: qty_on_hand ({$p->qty_on_hand}) != SUM(movements) ({$sumMovements}) untuk {$p->sku}."
            );
            $this->assertTrue($this->stockService->verifyStockIntegrity($p->id));
            $this->assertGreaterThanOrEqual(0, $p->qty_on_hand, "Stok tidak boleh minus (BR-S2).");
        }
    }

    /**
     * BR-S1: parts.qty_on_hand adalah cache dari SUM(stock_movements.qty_change).
     */
    public function test_br_s1_qty_on_hand_is_cache_of_movements_sum(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRS1',
            'name' => 'Oli Hidrolik ISO 68',
            'category' => 'Oli',
            'unit' => 'Liter',
            'sell_price' => 50000,
            'avg_cost' => 0,
            'qty_on_hand' => 0,
            'min_stock' => 10,
        ]);

        $this->stockService->recordPurchaseIn($part->id, 50, 35000);
        $this->stockService->recordServiceOut($part->id, 12, 1);
        $this->stockService->recordSaleOut($part->id, 8, 1);
        $this->stockService->recordAdjustment($part->id, 'ADJUSTMENT_IN', 5, 'Koreksi drum');

        $part->refresh();
        $expectedSum = 50 - 12 - 8 + 5; // 35
        $this->assertEquals($expectedSum, $part->qty_on_hand);
        $this->assertEquals($expectedSum, StockMovement::where('part_id', $part->id)->sum('qty_change'));
        $this->assertTrue($this->stockService->verifyStockIntegrity($part->id));
    }

    /**
     * BR-S2: Stok tidak boleh minus. Permintaan yang membuat stok < 0 ditolak dengan pesan jelas.
     */
    public function test_br_s2_stock_cannot_be_negative(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRS2',
            'name' => 'Gasket Cylinder Head',
            'category' => 'Seal & Gasket',
            'unit' => 'Pcs',
            'sell_price' => 300000,
            'avg_cost' => 200000,
            'qty_on_hand' => 2,
            'min_stock' => 1,
        ]);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessageMatches('/tidak mencukupi/i');

        // Minta keluar 5 unit padahal stok hanya 2
        $this->stockService->recordServiceOut($part->id, 5);
    }

    /**
     * BR-S3: Jenis pergerakan stok lengkap dan stock_movements tidak boleh diubah/dihapus (append-only).
     */
    public function test_br_s3_stock_movements_types_and_immutability(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRS3',
            'name' => 'Van Belt Fan',
            'category' => 'Engine',
            'unit' => 'Pcs',
            'sell_price' => 75000,
            'avg_cost' => 0,
            'qty_on_hand' => 0,
            'min_stock' => 2,
        ]);

        $m1 = $this->stockService->recordPurchaseIn($part->id, 10, 45000);
        $this->assertEquals('PURCHASE_IN', $m1->type);
        $this->assertEquals(10, $m1->qty_change);

        $m2 = $this->stockService->recordServiceOut($part->id, 3, 10);
        $this->assertEquals('SERVICE_OUT', $m2->type);
        $this->assertEquals(-3, $m2->qty_change);

        $m3 = $this->stockService->recordServiceReturn($part->id, 1, 45000, 10);
        $this->assertEquals('SERVICE_RETURN', $m3->type);
        $this->assertEquals(1, $m3->qty_change);

        $m4 = $this->stockService->recordSaleOut($part->id, 2, 20);
        $this->assertEquals('SALE_OUT', $m4->type);
        $this->assertEquals(-2, $m4->qty_change);

        $m5 = $this->stockService->recordSaleReturn($part->id, 1, 45000, 20);
        $this->assertEquals('SALE_RETURN', $m5->type);
        $this->assertEquals(1, $m5->qty_change);

        $m6 = $this->stockService->recordAdjustment($part->id, 'ADJUSTMENT_IN', 2, 'Opname fisik');
        $this->assertEquals('ADJUSTMENT_IN', $m6->type);

        $m7 = $this->stockService->recordAdjustment($part->id, 'ADJUSTMENT_OUT', 1, 'Koreksi rusak');
        $this->assertEquals('ADJUSTMENT_OUT', $m7->type);

        $this->assertCount(7, StockMovement::where('part_id', $part->id)->get());
    }

    /**
     * BR-S4: Sparepart ke order servis -> SERVICE_OUT. Retur / order dibatalkan -> SERVICE_RETURN.
     */
    public function test_br_s4_service_out_and_service_return_workflow(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRS4',
            'name' => 'Pin Bucket Excavator',
            'category' => 'Ground Engaging Tools',
            'unit' => 'Pcs',
            'sell_price' => 500000,
            'avg_cost' => 350000,
            'qty_on_hand' => 5,
            'min_stock' => 1,
        ]);

        // Dipakai di order servis WO-101
        $out = $this->stockService->recordServiceOut($part->id, 2, 101);
        $part->refresh();
        $this->assertEquals(3, $part->qty_on_hand);
        $this->assertEquals(350000, $out->unit_cost_snapshot);

        // Retur 1 unit karena batal dipasang
        $return = $this->stockService->recordServiceReturn($part->id, 1, $out->unit_cost_snapshot, 101);
        $part->refresh();
        $this->assertEquals(4, $part->qty_on_hand);
        $this->assertEquals(350000, $return->unit_cost_snapshot);
    }

    /**
     * BR-S5: Penjualan langsung -> SALE_OUT. Void invoice penjualan langsung -> SALE_RETURN.
     */
    public function test_br_s5_sale_out_and_sale_return_workflow(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRS5',
            'name' => 'Radiator Coolant 5L',
            'category' => 'Oli & Pelumas',
            'unit' => 'Galon',
            'sell_price' => 175000,
            'avg_cost' => 120000,
            'qty_on_hand' => 10,
            'min_stock' => 2,
        ]);

        // Invoice penjualan langsung INV-001 terbit
        $sale = $this->stockService->recordSaleOut($part->id, 4, 1);
        $part->refresh();
        $this->assertEquals(6, $part->qty_on_hand);

        // Void invoice penjualan langsung INV-001 -> stok kembali
        $return = $this->stockService->recordSaleReturn($part->id, 4, $sale->unit_cost_snapshot, 1);
        $part->refresh();
        $this->assertEquals(10, $part->qty_on_hand);
    }

    /**
     * BR-S6: Penyesuaian stok wajib alasan dan hanya admin.
     */
    public function test_br_s6_stock_adjustment_requires_reason_and_admin_only(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRS6',
            'name' => 'Switch Starter Solenoid',
            'category' => 'Kelistrikan',
            'unit' => 'Pcs',
            'sell_price' => 350000,
            'avg_cost' => 220000,
            'qty_on_hand' => 5,
            'min_stock' => 1,
        ]);

        // Alasan kosong wajib ditolak
        $this->expectException(InvalidStockMovementException::class);
        $this->stockService->recordAdjustment($part->id, 'ADJUSTMENT_IN', 2, '   ');
    }

    /**
     * BR-S7: Stok keluar tidak mengubah avg_cost, dan retur memakai snapshot sebagai biaya masuk.
     */
    public function test_br_s7_outbound_does_not_change_avg_cost_and_return_uses_snapshot(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRS7',
            'name' => 'Injector Nozzle',
            'category' => 'Engine',
            'unit' => 'Pcs',
            'sell_price' => 850000,
            'avg_cost' => 600000,
            'qty_on_hand' => 10,
            'min_stock' => 2,
        ]);

        // Stok keluar 4 unit: avg_cost TIDAK BOLEH berubah
        $this->stockService->recordServiceOut($part->id, 4, 1);
        $part->refresh();
        $this->assertEquals(6, $part->qty_on_hand);
        $this->assertEquals(600000, $part->avg_cost, 'Pengeluaran stok tidak boleh mengubah avg_cost (BR-S7).');

        // Retur 2 unit memakai snapshot 600.000: avg_cost tetap 600.000
        $this->stockService->recordServiceReturn($part->id, 2, 600000, 1);
        $part->refresh();
        $this->assertEquals(8, $part->qty_on_hand);
        $this->assertEquals(600000, $part->avg_cost);
    }

    /**
     * BR-S8: Setiap pergerakan keluar menyimpan unit_cost_snapshot = avg saat itu.
     */
    public function test_br_s8_outbound_stores_unit_cost_snapshot(): void
    {
        $part = Part::create([
            'sku' => 'SP-BRS8',
            'name' => 'Thermostat Mesin',
            'category' => 'Engine',
            'unit' => 'Pcs',
            'sell_price' => 250000,
            'avg_cost' => 180000,
            'qty_on_hand' => 5,
            'min_stock' => 1,
        ]);

        $out = $this->stockService->recordServiceOut($part->id, 2, 50);
        $this->assertEquals(180000, $out->unit_cost_snapshot);

        $sale = $this->stockService->recordSaleOut($part->id, 1, 75);
        $this->assertEquals(180000, $sale->unit_cost_snapshot);
    }
}
