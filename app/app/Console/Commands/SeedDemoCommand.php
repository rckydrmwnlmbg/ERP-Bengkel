<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerServiceRate;
use App\Models\EquipmentUnit;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Service;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedDemoCommand extends Command
{
    /**
     * Nama dan signature perintah artisan.
     */
    protected $signature = 'seed:demo {--fresh : Bersihkan database terlebih dahulu sebelum seeding}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Seed data demonstrasi realistis dan deterministik sesuai docs/SEED_SPEC.md (Fase E)';

    protected StockService $stockService;
    protected WorkOrderService $workOrderService;
    protected InvoiceService $invoiceService;
    protected PaymentService $paymentService;
    protected ReportService $reportService;

    public function __construct(
        StockService $stockService,
        WorkOrderService $workOrderService,
        InvoiceService $invoiceService,
        PaymentService $paymentService,
        ReportService $reportService
    ) {
        parent::__construct();
        $this->stockService = $stockService;
        $this->workOrderService = $workOrderService;
        $this->invoiceService = $invoiceService;
        $this->paymentService = $paymentService;
        $this->reportService = $reportService;
    }

    public function handle(): int
    {
        $this->info("=== Memulai Seeding Data Demonstrasi ERP Bengkel (SEED_SPEC.md - Fase E) ===");

        // 1. Database bersih bila flag --fresh diberikan atau default
        if ($this->option('fresh') || config('database.default') === 'sqlite') {
            $this->info("Menjalankan migrate:fresh untuk memastikan database bersih...");
            Artisan::call('migrate:fresh', ['--force' => true]);
        }

        // Optimasi SQLite WAL untuk eksekusi batch cepat
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA journal_mode=WAL;');
            DB::statement('PRAGMA synchronous=NORMAL;');
        }

        // 2. Deterministik: Seed angka acak dan anchor tanggal tetap
        mt_srand(42);
        srand(42);

        $timeline = [];

        // 3. Bangun Seluruh Jadwal Timeline Transaksi (Nov 2025 s.d. Okt 2026)
        $this->info("Menyiapkan timeline transaksi kronologis 12 bulan...");
        $this->buildChronologicalTimeline($timeline);

        // 4. Urutkan seluruh event berdasarkan waktu (strictly chronological)
        $this->info("Mengurutkan " . count($timeline) . " event secara kronologis...");
        usort($timeline, fn ($a, $b) => $a['time']->getTimestamp() <=> $b['time']->getTimestamp());

        // 5. Eksekusi Timeline Secara Berurutan
        $this->info("Mengeksekusi timeline simulasi bisnis...");
        $progressBar = $this->output->createProgressBar(count($timeline));
        $progressBar->start();

        foreach ($timeline as $event) {
            Carbon::setTestNow($event['time']);
            $event['action']();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // 6. Anchor di akhir seeding: 2026-10-20 23:59:59
        $finalAnchor = Carbon::parse('2026-10-20 23:59:59');
        Carbon::setTestNow($finalAnchor);

        // 7. Validasi Invarian (Wajib: gagal = seeding gagal)
        $this->info("Memverifikasi seluruh invarian bisnis SEED_SPEC & Fase E...");
        $this->assertAllInvariants();
        $this->info("✓ Seluruh invarian berhasil diverifikasi tanpa pelanggaran!");

        // 8. Cetak Ringkasan Angka Akhir
        $this->printSummary();

        Carbon::setTestNow(); // Kembalikan jam sistem

        $this->info("=== Seeding Data Demo Selesai dengan Sukses! ===");
        return Command::SUCCESS;
    }

    /**
     * Jadwalkan suatu event ke dalam timeline.
     */
    protected function schedule(array &$timeline, Carbon $time, callable $action): void
    {
        $timeline[] = [
            'time' => $time->copy(),
            'action' => $action,
        ];
    }

    /**
     * Membangun seluruh timeline kronologis dari 2025-11-01 s.d. 2026-10-20.
     */
    protected function buildChronologicalTimeline(array &$timeline): void
    {
        // Variabel penampung lintas-event
        $users = [];
        $customers = [];
        $units = [];
        $services = [];
        $parts = [];
        $partsSpec = [];
        $workOrders = [];
        $woInvoices = [];
        $csInvoices = [];

        // --- EVENT 1: Master Data Setup (2025-11-01 08:00:00) ---
        $this->schedule($timeline, Carbon::parse('2025-11-01 08:00:00'), function () use (&$users, &$customers, &$units, &$services, &$parts, &$partsSpec) {
            // Pengaturan
            Setting::set('shop_name', 'Bengkel Alat Berat & Toko Sparepart Nusantara');
            Setting::set('tax_rate_percent', 0);

            // Satu set user tanpa duplikat (README & welcome page)
            $users['admin'] = User::create([
                'name' => 'Administrator Demo',
                'email' => 'admin@bengkel.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]);

            $users['cashier'] = User::create([
                'name' => 'Kasir Demo',
                'email' => 'kasir@bengkel.com',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'is_active' => true,
            ]);

            $users['mechanics'] = [
                User::create([
                    'name' => 'Budi Mekanik Senior',
                    'email' => 'mekanik1@bengkel.com',
                    'password' => Hash::make('password'),
                    'role' => 'mechanic',
                    'is_active' => true,
                ]),
                User::create([
                    'name' => 'Agus Mekanik Hidrolik',
                    'email' => 'mekanik2@bengkel.com',
                    'password' => Hash::make('password'),
                    'role' => 'mechanic',
                    'is_active' => true,
                ]),
            ];

            // 11 Customers
            $customersData = [
                ['code' => 'CUST-UMUM', 'name' => 'Pelanggan Umum', 'type' => 'individual', 'payment_term_days' => 0, 'phone' => '-'],
                ['code' => 'CUST-BTP', 'name' => 'PT Borneo Tambang Pratama', 'type' => 'company', 'payment_term_days' => 30, 'phone' => '0811550101'],
                ['code' => 'CUST-MMM', 'name' => 'PT Mahakam Mineral Mandiri', 'type' => 'company', 'payment_term_days' => 45, 'phone' => '0811550102'],
                ['code' => 'CUST-ALP', 'name' => 'PT Andalas Logistik Perkasa', 'type' => 'company', 'payment_term_days' => 14, 'phone' => '0811550103'],
                ['code' => 'CUST-NRA', 'name' => 'CV Nusantara Rental Alat', 'type' => 'company', 'payment_term_days' => 30, 'phone' => '0811550104'],
                ['code' => 'CUST-SKS', 'name' => 'PT Samudra Karya Sejahtera', 'type' => 'company', 'payment_term_days' => 30, 'phone' => '0811550105'],
                ['code' => 'CUST-JKS', 'name' => 'Bapak Joko Susilo', 'type' => 'individual', 'payment_term_days' => 14, 'phone' => '0812340106'],
                ['code' => 'CUST-HRH', 'name' => 'Haji Rahmat Hidayat', 'type' => 'individual', 'payment_term_days' => 0, 'phone' => '0812340107'],
                ['code' => 'CUST-HDK', 'name' => 'Bapak Hendra Kusuma', 'type' => 'individual', 'payment_term_days' => 14, 'phone' => '0812340108'],
                ['code' => 'CUST-KAB', 'name' => 'Koperasi Angkutan Bersama', 'type' => 'company', 'payment_term_days' => 30, 'phone' => '0813450109'],
                ['code' => 'CUST-MUM', 'name' => 'Mitra Usaha Mandiri', 'type' => 'company', 'payment_term_days' => 14, 'phone' => '0813450110'],
            ];

            foreach ($customersData as $c) {
                $customers[$c['code']] = Customer::create($c);
            }

            // 18 Units
            $unitsData = [
                ['cust' => 'CUST-BTP', 'brand' => 'Komatsu', 'model' => 'PC200-8', 'sn' => 'KMTPC200-8801', 'code' => 'EXC-01', 'hm' => 6500],
                ['cust' => 'CUST-BTP', 'brand' => 'Caterpillar', 'model' => '320D', 'sn' => 'CAT320D-9902', 'code' => 'EXC-02', 'hm' => 8200],
                ['cust' => 'CUST-BTP', 'brand' => 'Komatsu', 'model' => 'D85ESS-2', 'sn' => 'KMTD85-7703', 'code' => 'DOZ-01', 'hm' => 11400],
                ['cust' => 'CUST-MMM', 'brand' => 'Hitachi', 'model' => 'ZX200-5G', 'sn' => 'HTZX200-5504', 'code' => 'EXC-03', 'hm' => 5400],
                ['cust' => 'CUST-MMM', 'brand' => 'Caterpillar', 'model' => '336D', 'sn' => 'CAT336D-4405', 'code' => 'EXC-04', 'hm' => 9100],
                ['cust' => 'CUST-MMM', 'brand' => 'Volvo', 'model' => 'FMX400', 'sn' => 'VLVFMX-3306', 'code' => 'DMP-01', 'hm' => 14200],
                ['cust' => 'CUST-ALP', 'brand' => 'Scania', 'model' => 'P360', 'sn' => 'SCNP360-2207', 'code' => 'TRK-01', 'hm' => 18500],
                ['cust' => 'CUST-ALP', 'brand' => 'Hino', 'model' => '500 FM260JD', 'sn' => 'HNO500-1108', 'code' => 'DMP-02', 'hm' => 16300],
                ['cust' => 'CUST-NRA', 'brand' => 'Kobelco', 'model' => 'SK200-8', 'sn' => 'KBLSK200-8809', 'code' => 'EXC-05', 'hm' => 7800],
                ['cust' => 'CUST-NRA', 'brand' => 'Komatsu', 'model' => 'WA380-6', 'sn' => 'KMTWA380-7710', 'code' => 'LDR-01', 'hm' => 6100],
                ['cust' => 'CUST-NRA', 'brand' => 'Caterpillar', 'model' => 'D6R', 'sn' => 'CATD6R-6611', 'code' => 'DOZ-02', 'hm' => 12500],
                ['cust' => 'CUST-SKS', 'brand' => 'Komatsu', 'model' => 'GD511A-1', 'sn' => 'KMTGD511-5512', 'code' => 'GRD-01', 'hm' => 8900],
                ['cust' => 'CUST-SKS', 'brand' => 'Caterpillar', 'model' => '950H', 'sn' => 'CAT950H-4413', 'code' => 'LDR-02', 'hm' => 7300],
                ['cust' => 'CUST-JKS', 'brand' => 'Komatsu', 'model' => 'PC200-7', 'sn' => 'KMTPC200-3314', 'code' => 'EXC-06', 'hm' => 13400],
                ['cust' => 'CUST-HRH', 'brand' => 'Hitachi', 'model' => 'ZX330', 'sn' => 'HTZX330-2215', 'code' => 'EXC-07', 'hm' => 9700],
                ['cust' => 'CUST-HDK', 'brand' => 'Mitsubishi', 'model' => 'Fuso FN527', 'sn' => 'FSOFN527-1116', 'code' => 'DMP-03', 'hm' => 15800],
                ['cust' => 'CUST-KAB', 'brand' => 'Hino', 'model' => '500 FM260JD', 'sn' => 'HNO500-9917', 'code' => 'DMP-04', 'hm' => 17100],
                ['cust' => 'CUST-MUM', 'brand' => 'Shantui', 'model' => 'SD22', 'sn' => 'SHTSD22-8818', 'code' => 'DOZ-03', 'hm' => 8400],
            ];

            foreach ($unitsData as $u) {
                $customer = $customers[$u['cust']];
                $units[] = EquipmentUnit::create([
                    'customer_id' => $customer->id,
                    'brand' => $u['brand'],
                    'model' => $u['model'],
                    'serial_number' => $u['sn'],
                    'unit_code' => $u['code'],
                    'hour_meter' => $u['hm'],
                ]);
            }

            // 14 Services
            $servicesData = [
                ['code' => 'SRV-001', 'name' => 'Ganti Oli Mesin & Filter', 'category' => 'Periodic Service', 'default_price' => 250000],
                ['code' => 'SRV-002', 'name' => 'Service Berkala 250 Jam', 'category' => 'Periodic Service', 'default_price' => 750000],
                ['code' => 'SRV-003', 'name' => 'Service Berkala 500 Jam', 'category' => 'Periodic Service', 'default_price' => 1500000],
                ['code' => 'SRV-004', 'name' => 'Service Berkala 1000 Jam', 'category' => 'Periodic Service', 'default_price' => 3500000],
                ['code' => 'SRV-005', 'name' => 'Perbaikan Sistem Hidrolik', 'category' => 'Hydraulic', 'default_price' => 1200000],
                ['code' => 'SRV-006', 'name' => 'Overhaul Main Control Valve (MCV)', 'category' => 'Hydraulic', 'default_price' => 4500000],
                ['code' => 'SRV-007', 'name' => 'Overhaul Pompa Hidrolik Utama', 'category' => 'Hydraulic', 'default_price' => 6000000],
                ['code' => 'SRV-008', 'name' => 'Ganti Seal Kit Cylinder Boom/Arm', 'category' => 'Hydraulic', 'default_price' => 850000],
                ['code' => 'SRV-009', 'name' => 'Ganti Track Link & Roller Undercarriage', 'category' => 'Undercarriage', 'default_price' => 2500000],
                ['code' => 'SRV-010', 'name' => 'Las & Hardfacing Plat Bucket', 'category' => 'Welding', 'default_price' => 1800000],
                ['code' => 'SRV-011', 'name' => 'Kalibrasi Injector & FIP Diesel', 'category' => 'Engine', 'default_price' => 2000000],
                ['code' => 'SRV-012', 'name' => 'Kunjungan Mekanik & Inspeksi Lapangan', 'category' => 'Field Service', 'default_price' => 1000000],
                ['code' => 'SRV-013', 'name' => 'Overhaul Total Mesin Diesel Heavy Duty', 'category' => 'Engine', 'default_price' => 15000000],
                ['code' => 'SRV-014', 'name' => 'Perbaikan Sistem Elektrikal & Starting Motor', 'category' => 'Electrical', 'default_price' => 650000],
            ];

            foreach ($servicesData as $s) {
                $services[$s['code']] = Service::create($s);
            }

            // Special rates for 3 customers
            CustomerServiceRate::create(['customer_id' => $customers['CUST-BTP']->id, 'service_id' => $services['SRV-002']->id, 'price' => 650000]);
            CustomerServiceRate::create(['customer_id' => $customers['CUST-BTP']->id, 'service_id' => $services['SRV-003']->id, 'price' => 1300000]);
            CustomerServiceRate::create(['customer_id' => $customers['CUST-BTP']->id, 'service_id' => $services['SRV-004']->id, 'price' => 3000000]);
            CustomerServiceRate::create(['customer_id' => $customers['CUST-BTP']->id, 'service_id' => $services['SRV-012']->id, 'price' => 800000]);

            CustomerServiceRate::create(['customer_id' => $customers['CUST-ALP']->id, 'service_id' => $services['SRV-001']->id, 'price' => 200000]);
            CustomerServiceRate::create(['customer_id' => $customers['CUST-ALP']->id, 'service_id' => $services['SRV-002']->id, 'price' => 680000]);
            CustomerServiceRate::create(['customer_id' => $customers['CUST-ALP']->id, 'service_id' => $services['SRV-014']->id, 'price' => 550000]);

            CustomerServiceRate::create(['customer_id' => $customers['CUST-NRA']->id, 'service_id' => $services['SRV-005']->id, 'price' => 1000000]);
            CustomerServiceRate::create(['customer_id' => $customers['CUST-NRA']->id, 'service_id' => $services['SRV-008']->id, 'price' => 750000]);
            CustomerServiceRate::create(['customer_id' => $customers['CUST-NRA']->id, 'service_id' => $services['SRV-010']->id, 'price' => 1500000]);
            CustomerServiceRate::create(['customer_id' => $customers['CUST-NRA']->id, 'service_id' => $services['SRV-012']->id, 'price' => 750000]);

            // 5 Suppliers
            $suppliersData = [
                ['name' => 'PT Distributor Filter Indo', 'phone' => '021-555101'],
                ['name' => 'PT Pelumas Prima Nusantara', 'phone' => '021-555102'],
                ['name' => 'PT Undercarriage Perkasa', 'phone' => '021-555103'],
                ['name' => 'PT Cipta Seal & Hidrolik Mandiri', 'phone' => '021-555104'],
                ['name' => 'PT Mega Heavy Parts Internasional', 'phone' => '021-555105'],
            ];

            foreach ($suppliersData as $s) {
                Supplier::create($s);
            }

            // 45 Parts Master (Stock starts at 0)
            $partsSpec = [
                ['sku' => 'SP-0001', 'name' => 'Filter Oli Mesin HD', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 85000, 'min' => 5],
                ['sku' => 'SP-0002', 'name' => 'Filter Solar Primer Racor', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 120000, 'min' => 5],
                ['sku' => 'SP-0003', 'name' => 'Filter Solar Sekunder Spin-On', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 95000, 'min' => 5],
                ['sku' => 'SP-0004', 'name' => 'Filter Udara Luar Outer Air', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 220000, 'min' => 3],
                ['sku' => 'SP-0005', 'name' => 'Filter Udara Dalam Inner Safety', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 140000, 'min' => 2],
                ['sku' => 'SP-0006', 'name' => 'Filter Hidrolik Return Line', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 280000, 'min' => 4],
                ['sku' => 'SP-0007', 'name' => 'Filter Hidrolik Suction Strainer', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 310000, 'min' => 3],
                ['sku' => 'SP-0008', 'name' => 'Filter Transmisi Powershift', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 190000, 'min' => 3],

                ['sku' => 'SP-0009', 'name' => 'Oli Mesin Diesel 15W-40 Drum 200L', 'category' => 'Oli & Pelumas', 'unit' => 'Drum', 'base_cost' => 6200000, 'min' => 1],
                ['sku' => 'SP-0010', 'name' => 'Oli Mesin Diesel 15W-40 Pail 20L', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 680000, 'min' => 4],
                ['sku' => 'SP-0011', 'name' => 'Oli Hidrolik ISO VG 68 Pail 20L', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 640000, 'min' => 4],
                ['sku' => 'SP-0012', 'name' => 'Oli Hidrolik ISO VG 46 Drum 200L', 'category' => 'Oli & Pelumas', 'unit' => 'Drum', 'base_cost' => 5800000, 'min' => 1],
                ['sku' => 'SP-0013', 'name' => 'Oli Transmisi SAE 50 Pail 20L', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 720000, 'min' => 3],
                ['sku' => 'SP-0014', 'name' => 'Grease Heavy Duty EP-2 Pail 15Kg', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 550000, 'min' => 3],
                ['sku' => 'SP-0015', 'name' => 'Coolant Radiator Premix 50/50 Pail 20L', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 420000, 'min' => 2],

                ['sku' => 'SP-0016', 'name' => 'Selang Hidrolik 2-Wire 3/4 inch 2M', 'category' => 'Hidrolik', 'unit' => 'Pcs', 'base_cost' => 260000, 'min' => 4],
                ['sku' => 'SP-0017', 'name' => 'Selang Hidrolik 4-Spiral 1 inch 3M', 'category' => 'Hidrolik', 'unit' => 'Pcs', 'base_cost' => 580000, 'min' => 3],
                ['sku' => 'SP-0018', 'name' => 'Fitting Flange / Adaptor NPT 3/4', 'category' => 'Hidrolik', 'unit' => 'Pcs', 'base_cost' => 45000, 'min' => 10],
                ['sku' => 'SP-0019', 'name' => 'Quick Coupler Hidrolik 1/2 High Pressure', 'category' => 'Hidrolik', 'unit' => 'Set', 'base_cost' => 320000, 'min' => 3],
                ['sku' => 'SP-0020', 'name' => 'Rod Cylinder Boom Excavator 20-Ton', 'category' => 'Hidrolik', 'unit' => 'Pcs', 'base_cost' => 4200000, 'min' => 1],

                ['sku' => 'SP-0021', 'name' => 'Track Shoe PC200 600mm Triple Grouser', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 480000, 'min' => 6],
                ['sku' => 'SP-0022', 'name' => 'Track Link Assembly PC200 45-Link', 'category' => 'Undercarriage', 'unit' => 'Set', 'base_cost' => 7500000, 'min' => 1],
                ['sku' => 'SP-0023', 'name' => 'Track Roller Single Flange PC200', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 620000, 'min' => 4],
                ['sku' => 'SP-0024', 'name' => 'Track Roller Double Flange PC200', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 680000, 'min' => 4],
                ['sku' => 'SP-0025', 'name' => 'Carrier Roller / Top Roller PC200', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 520000, 'min' => 3],
                ['sku' => 'SP-0026', 'name' => 'Sprocket Segment Teeth D85', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 880000, 'min' => 3],
                ['sku' => 'SP-0027', 'name' => 'Front Idler Assembly PC200', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 3800000, 'min' => 1],

                ['sku' => 'SP-0028', 'name' => 'Piston Engine Diesel 6D102', 'category' => 'Engine', 'unit' => 'Set', 'base_cost' => 1450000, 'min' => 2],
                ['sku' => 'SP-0029', 'name' => 'Ring Piston Set 6-Cylinder', 'category' => 'Engine', 'unit' => 'Set', 'base_cost' => 850000, 'min' => 2],
                ['sku' => 'SP-0030', 'name' => 'Liner Cylinder / Boring 6D102', 'category' => 'Engine', 'unit' => 'Pcs', 'base_cost' => 620000, 'min' => 4],
                ['sku' => 'SP-0031', 'name' => 'Nozzle Injector Common Rail', 'category' => 'Engine', 'unit' => 'Pcs', 'base_cost' => 1250000, 'min' => 4],
                ['sku' => 'SP-0032', 'name' => 'V-Belt Fan & Alternator Set', 'category' => 'Engine', 'unit' => 'Set', 'base_cost' => 160000, 'min' => 4],
                ['sku' => 'SP-0033', 'name' => 'Water Pump Assembly Engine', 'category' => 'Engine', 'unit' => 'Pcs', 'base_cost' => 950000, 'min' => 2],
                ['sku' => 'SP-0034', 'name' => 'Turbocharger Assembly PC200-8', 'category' => 'Engine', 'unit' => 'Pcs', 'base_cost' => 5500000, 'min' => 1],

                ['sku' => 'SP-0035', 'name' => 'Aki Basah Heavy Duty 12V 100Ah', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 1350000, 'min' => 3],
                ['sku' => 'SP-0036', 'name' => 'Aki Maintenance Free 12V 150Ah', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 2100000, 'min' => 2],
                ['sku' => 'SP-0037', 'name' => 'Alternator 24V 60A HD', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 1850000, 'min' => 2],
                ['sku' => 'SP-0038', 'name' => 'Starter Motor 24V 7.5kW', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 2450000, 'min' => 1],
                ['sku' => 'SP-0039', 'name' => 'Lampu Kerja LED Worklamp 48W 24V', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 175000, 'min' => 6],
                ['sku' => 'SP-0040', 'name' => 'Sensor Tekanan Oli Engine 24V', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 350000, 'min' => 3],

                ['sku' => 'SP-0041', 'name' => 'Tooth Bucket Standard PC200', 'category' => 'Ground Engaging Tools', 'unit' => 'Pcs', 'base_cost' => 135000, 'min' => 10],
                ['sku' => 'SP-0042', 'name' => 'Pin & Retainer Lock Tooth Bucket', 'category' => 'Ground Engaging Tools', 'unit' => 'Set', 'base_cost' => 25000, 'min' => 20],
                ['sku' => 'SP-0043', 'name' => 'Side Cutter Bucket Kiri & Kanan', 'category' => 'Ground Engaging Tools', 'unit' => 'Set', 'base_cost' => 780000, 'min' => 3],
                ['sku' => 'SP-0044', 'name' => 'Cutting Edge Blade Bulldozer D85', 'category' => 'Ground Engaging Tools', 'unit' => 'Pcs', 'base_cost' => 1100000, 'min' => 3],

                ['sku' => 'SP-0045', 'name' => 'Seal Kit Boom Cylinder PC200', 'category' => 'Seal & Gasket', 'unit' => 'Set', 'base_cost' => 450000, 'min' => 4],
            ];

            foreach ($partsSpec as $spec) {
                $marginPercent = rand(18, 42);
                $sellPrice = (int) round($spec['base_cost'] * (1 + $marginPercent / 100), -3);

                $part = Part::create([
                    'sku' => $spec['sku'],
                    'name' => $spec['name'],
                    'category' => $spec['category'],
                    'unit' => $spec['unit'],
                    'sell_price' => $sellPrice,
                    'avg_cost' => $spec['base_cost'],
                    'qty_on_hand' => 0,
                    'min_stock' => $spec['min'],
                    'is_active' => true,
                ]);

                $parts[$spec['sku']] = $part;
            }
        });

        // Helper untuk menjadwalkan pembelian
        $schedulePurchase = function (Carbon $time, string $ref, int $supplierId, callable $itemsCallback) use (&$timeline) {
            $this->schedule($timeline, $time, function () use ($time, $ref, $supplierId, $itemsCallback) {
                $supplier = Supplier::find($supplierId) ?? Supplier::first();
                $admin = User::where('role', 'admin')->first();
                $items = $itemsCallback();

                $total = 0;
                foreach ($items as $item) {
                    $total += ((int) $item['qty'] * (int) $item['unit_cost']);
                }

                DB::transaction(function () use ($supplier, $time, $ref, $items, $total, $admin) {
                    $purchase = Purchase::create([
                        'supplier_id' => $supplier->id,
                        'purchase_date' => $time->toDateString(),
                        'reference' => $ref,
                        'total' => $total,
                        'created_by' => $admin->id,
                    ]);

                    foreach ($items as $item) {
                        PurchaseItem::create([
                            'purchase_id' => $purchase->id,
                            'part_id' => $item['part_id'],
                            'qty' => $item['qty'],
                            'unit_cost' => $item['unit_cost'],
                        ]);

                        $this->stockService->recordPurchaseIn(
                            partId: $item['part_id'],
                            qty: $item['qty'],
                            unitCost: $item['unit_cost'],
                            purchaseId: $purchase->id,
                            reference: $ref,
                            userId: $admin->id
                        );
                    }
                });
            });
        };

        // --- EVENT 2: PO-1 (2025-11-02 08:30:00) ---
        $schedulePurchase(Carbon::parse('2025-11-02 08:30:00'), 'PO-202511-0001', 1, function () use (&$parts, &$partsSpec) {
            $items = [];
            foreach ($parts as $sku => $part) {
                $spec = collect($partsSpec)->firstWhere('sku', $sku);
                $cost = $spec['base_cost'];
                if ($sku === 'SP-0020') {
                    $qty = 3; // akan dikonsumsi hingga 0 pada Oktober
                } elseif (in_array($sku, ['SP-0022', 'SP-0027', 'SP-0034', 'SP-0038'], true)) {
                    $qty = 3; // akan tersisa 1 di akhir (min_stock = 1)
                } elseif (in_array($sku, ['SP-0009', 'SP-0012'], true)) {
                    $qty = 60; // drum oli mesin & hidrolik
                } elseif ($spec['base_cost'] >= 1000000) {
                    $qty = rand(8, 12);
                } elseif (in_array($part->category, ['Filter', 'Oli & Pelumas', 'Ground Engaging Tools'], true)) {
                    $qty = rand(80, 110);
                } else {
                    $qty = rand(25, 40);
                }

                $items[] = [
                    'part_id' => $part->id,
                    'qty' => $qty,
                    'unit_cost' => $cost,
                ];
            }
            return $items;
        });

        // --- EVENT 3: Pembelian Ulang PO-2, PO-3, PO-4, PO-5 ---
        // PO-2: Feb 8, 2026
        $schedulePurchase(Carbon::parse('2026-02-08 09:00:00'), 'PO-202602-0002', 2, function () use (&$parts, &$partsSpec) {
            $items = [];
            for ($i = 1; $i <= 12; $i++) {
                $sku = sprintf('SP-%04d', $i);
                $spec = collect($partsSpec)->firstWhere('sku', $sku);
                $qty = in_array($sku, ['SP-0009', 'SP-0012'], true) ? 35 : rand(40, 60);
                $items[] = ['part_id' => $parts[$sku]->id, 'qty' => $qty, 'unit_cost' => (int) round($spec['base_cost'] * 1.08, -3)];
            }
            return $items;
        });

        // PO-3: Mei 8, 2026
        $schedulePurchase(Carbon::parse('2026-05-08 09:00:00'), 'PO-202605-0003', 3, function () use (&$parts, &$partsSpec) {
            $items = [];
            for ($i = 13; $i <= 27; $i++) {
                $sku = sprintf('SP-%04d', $i);
                if (in_array($sku, ['SP-0020', 'SP-0022', 'SP-0027'], true)) continue;
                $spec = collect($partsSpec)->firstWhere('sku', $sku);
                $items[] = ['part_id' => $parts[$sku]->id, 'qty' => rand(25, 40), 'unit_cost' => (int) round($spec['base_cost'] * 1.10, -3)];
            }
            $items[] = ['part_id' => $parts['SP-0009']->id, 'qty' => 30, 'unit_cost' => (int) round(6200000 * 1.08, -3)];
            $items[] = ['part_id' => $parts['SP-0012']->id, 'qty' => 30, 'unit_cost' => (int) round(5800000 * 1.08, -3)];
            return $items;
        });

        // PO-4: Jul 8, 2026
        $schedulePurchase(Carbon::parse('2026-07-08 09:00:00'), 'PO-202607-0004', 4, function () use (&$parts, &$partsSpec) {
            $items = [];
            for ($i = 28; $i <= 42; $i++) {
                $sku = sprintf('SP-%04d', $i);
                if (in_array($sku, ['SP-0034', 'SP-0038'], true)) continue;
                $spec = collect($partsSpec)->firstWhere('sku', $sku);
                $items[] = ['part_id' => $parts[$sku]->id, 'qty' => rand(25, 40), 'unit_cost' => (int) round($spec['base_cost'] * 1.08, -3)];
            }
            $items[] = ['part_id' => $parts['SP-0009']->id, 'qty' => 30, 'unit_cost' => (int) round(6200000 * 1.10, -3)];
            $items[] = ['part_id' => $parts['SP-0012']->id, 'qty' => 30, 'unit_cost' => (int) round(5800000 * 1.10, -3)];
            return $items;
        });

        // PO-5: Sep 5, 2026
        $schedulePurchase(Carbon::parse('2026-09-05 09:00:00'), 'PO-202609-0005', 5, function () use (&$parts, &$partsSpec) {
            $items = [];
            for ($i = 1; $i <= 10; $i++) {
                $sku = sprintf('SP-%04d', $i);
                $spec = collect($partsSpec)->firstWhere('sku', $sku);
                $items[] = ['part_id' => $parts[$sku]->id, 'qty' => rand(30, 50), 'unit_cost' => (int) round($spec['base_cost'] * 1.06, -3)];
            }
            $items[] = ['part_id' => $parts['SP-0012']->id, 'qty' => 20, 'unit_cost' => (int) round(5800000 * 1.10, -3)];
            return $items;
        });

        // --- EVENT 4: Penyesuaian Stok Terjadwal ---
        // 2026-02-15 11:00:00: Adjustment IN SP-0018 (+5)
        $this->schedule($timeline, Carbon::parse('2026-02-15 11:00:00'), function () use (&$parts) {
            $admin = User::where('role', 'admin')->first();
            $this->stockService->recordAdjustment($parts['SP-0018']->id, 'ADJUSTMENT_IN', 5, 'Hasil Stock Opname triwulan I - kelebihan fisik fitting', $admin->id);
        });

        // 2026-06-15 15:30:00: Adjustment OUT SP-0032 (-1)
        $this->schedule($timeline, Carbon::parse('2026-06-15 15:30:00'), function () use (&$parts) {
            $admin = User::where('role', 'admin')->first();
            $this->stockService->recordAdjustment($parts['SP-0032']->id, 'ADJUSTMENT_OUT', 1, 'Hasil Stock Opname triwulan II - 1 pcs v-belt robek di gudang', $admin->id);
        });

        // --- EVENT 5: Rencana Bulanan (12 Bulan) ---
        $monthlyPlan = [
            ['m' => 1,  'year' => 2025, 'month' => 11, 'days' => 30, 'wo_count' => 6,  'cs_count' => 3],
            ['m' => 2,  'year' => 2025, 'month' => 12, 'days' => 31, 'wo_count' => 7,  'cs_count' => 4],
            ['m' => 3,  'year' => 2026, 'month' => 1,  'days' => 31, 'wo_count' => 8,  'cs_count' => 4],
            ['m' => 4,  'year' => 2026, 'month' => 2,  'days' => 28, 'wo_count' => 8,  'cs_count' => 4],
            ['m' => 5,  'year' => 2026, 'month' => 3,  'days' => 31, 'wo_count' => 9,  'cs_count' => 5],
            ['m' => 6,  'year' => 2026, 'month' => 4,  'days' => 30, 'wo_count' => 9,  'cs_count' => 5],
            ['m' => 7,  'year' => 2026, 'month' => 5,  'days' => 31, 'wo_count' => 10, 'cs_count' => 6],
            ['m' => 8,  'year' => 2026, 'month' => 6,  'days' => 30, 'wo_count' => 10, 'cs_count' => 5],
            ['m' => 9,  'year' => 2026, 'month' => 7,  'days' => 31, 'wo_count' => 11, 'cs_count' => 6],
            ['m' => 10, 'year' => 2026, 'month' => 8,  'days' => 31, 'wo_count' => 11, 'cs_count' => 6],
            ['m' => 11, 'year' => 2026, 'month' => 9,  'days' => 30, 'wo_count' => 11, 'cs_count' => 7],
            ['m' => 12, 'year' => 2026, 'month' => 10, 'days' => 20, 'wo_count' => 10, 'cs_count' => 6],
        ];

        // Jadwal Beban Operasional (Expenses)
        foreach ($monthlyPlan as $plan) {
            $y = $plan['year'];
            $mo = $plan['month'];
            $mNum = $plan['m'];

            $this->schedule($timeline, Carbon::create($y, $mo, 2, 8, 0), function () use ($y, $mo) {
                $admin = User::where('role', 'admin')->first();
                Expense::create([
                    'expense_date' => Carbon::create($y, $mo, 2)->toDateString(),
                    'category' => 'Sewa Tempat',
                    'description' => 'Sewa workshop ' . Carbon::create($y, $mo, 1)->translatedFormat('F Y'),
                    'amount' => 3500000,
                    'created_by' => $admin->id,
                ]);
            });

            $this->schedule($timeline, Carbon::create($y, $mo, 10, 10, 0), function () use ($y, $mo) {
                $admin = User::where('role', 'admin')->first();
                Expense::create([
                    'expense_date' => Carbon::create($y, $mo, 10)->toDateString(),
                    'category' => 'Listrik & Air',
                    'description' => 'Tagihan listrik & air workshop ' . Carbon::create($y, $mo, 1)->translatedFormat('F Y'),
                    'amount' => rand(480000, 720000),
                    'created_by' => $admin->id,
                ]);
            });

            $this->schedule($timeline, Carbon::create($y, $mo, 18, 12, 0), function () use ($y, $mo) {
                $admin = User::where('role', 'admin')->first();
                Expense::create([
                    'expense_date' => Carbon::create($y, $mo, 18)->toDateString(),
                    'category' => 'Konsumsi',
                    'description' => 'Konsumsi & kopi mekanik pengerjaan lembur',
                    'amount' => rand(180000, 320000),
                    'created_by' => $admin->id,
                ]);
            });

            if ($mNum % 2 === 1) {
                $this->schedule($timeline, Carbon::create($y, $mo, 24, 14, 0), function () use ($y, $mo) {
                    $admin = User::where('role', 'admin')->first();
                    Expense::create([
                        'expense_date' => Carbon::create($y, $mo, 24)->toDateString(),
                        'category' => 'Transportasi & Logistik',
                        'description' => 'Biaya solar armada servis keliling',
                        'amount' => rand(350000, 550000),
                        'created_by' => $admin->id,
                    ]);
                });
            }
        }

        // --- EVENT 6: Jadwal Order Servis (110) & Penjualan Toko (60) ---
        $globalWo = 0;
        $globalCs = 0;
        $installmentTargetCount = 0;
        $discountTargetCount = 0;
        $maxSimDate = Carbon::parse('2026-10-20 16:00:00');

        foreach ($monthlyPlan as $plan) {
            $mNum = $plan['m'];
            $y = $plan['year'];
            $mo = $plan['month'];
            $maxDay = $plan['days'];
            $woInMonth = $plan['wo_count'];
            $csInMonth = $plan['cs_count'];

            // Jadwalkan Work Orders
            for ($w = 0; $w < $woInMonth; $w++) {
                $globalWo++;
                $woIndex = $globalWo;
                $day = min($maxDay, max(1, (int) round(($w + 1) * ($maxDay / ($woInMonth + 1)))));
                $woTime = Carbon::create($y, $mo, $day, 8, 30);

                $isCancelled = ($woIndex === 15 || $woIndex === 48);
                $isOpen = ($mNum === 12 && ($w === 8 || $w === 9));
                $isInProgress = ($mNum === 12 && ($w === 6 || $w === 7));
                $isCompletedUninvoiced = ($mNum === 12 && ($w >= 3 && $w <= 5));

                // 1. Pembuatan WO dan Penambahan Part & Jasa
                $this->schedule($timeline, $woTime, function () use ($woIndex, $w, $mNum, $woTime, &$workOrders, &$units, &$services, &$parts, &$users) {
                    $unitsList = array_values($units);
                    $servicesList = array_values($services);
                    $mechanics = $users['mechanics'];
                    $admin = $users['admin'];

                    $unit = $unitsList[$woIndex % count($unitsList)];
                    $customer = $unit->customer;
                    $mechanic = $mechanics[$woIndex % count($mechanics)];

                    $wo = $this->workOrderService->createWorkOrder([
                        'customer_id' => $customer->id,
                        'equipment_unit_id' => $unit->id,
                        'complaint' => 'Servis berkala dan perbaikan unit nomor kerja ' . $woIndex,
                        'diagnosis' => 'Pemeriksaan rutin komponen mesin dan penggantian pelumas',
                        'mechanic_id' => $mechanic->id,
                        'opened_at' => $woTime,
                    ], $admin->id);
                    $workOrders[$woIndex] = $wo;

                    // Pemilihan Jasa
                    if ($w === 0) {
                        $service = $services['SRV-013']; // Overhaul 15M
                    } elseif ($w === 1) {
                        $service = ($mNum >= 6) ? $services['SRV-007'] : $services['SRV-006']; // 6M / 4.5M
                    } elseif ($w === 2) {
                        $service = ($mNum >= 3) ? $services['SRV-007'] : $services['SRV-009']; // 6M / 2.5M
                    } elseif ($w === 3) {
                        $service = $services['SRV-004']; // 3.5M
                    } else {
                        $serviceKeys = ['SRV-002', 'SRV-003', 'SRV-005', 'SRV-008', 'SRV-010', 'SRV-011', 'SRV-012', 'SRV-014'];
                        $service = $services[$serviceKeys[($woIndex + $w) % count($serviceKeys)]];
                    }
                    $this->workOrderService->addServiceItem($wo, $service->id, 1, $admin->id);

                    // Pemakaian part target penipisan stok secara natural
                    if ($mNum === 3 && $w === 1) {
                        $p = $parts['SP-0020']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 7 && $w === 1) {
                        $p = $parts['SP-0020']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 10 && $w === 1) {
                        $p = $parts['SP-0020']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 2 && $w === 2) {
                        $p = $parts['SP-0022']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 6 && $w === 2) {
                        $p = $parts['SP-0022']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 4 && $w === 2) {
                        $p = $parts['SP-0027']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 8 && $w === 2) {
                        $p = $parts['SP-0027']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 5 && $w === 2) {
                        $p = $parts['SP-0034']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 9 && $w === 2) {
                        $p = $parts['SP-0034']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 3 && $w === 2) {
                        $p = $parts['SP-0038']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    } elseif ($mNum === 11 && $w === 2) {
                        $p = $parts['SP-0038']; $p->refresh();
                        if ($p->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $p->id, 1, null, $admin->id);
                    }

                    // Part pendukung reguler
                    if ($service->code === 'SRV-013') {
                        $pPart = $parts['SP-0028']; $pPart->refresh();
                        if ($pPart->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $pPart->id, 1, null, $admin->id);
                        $pOil = $parts['SP-0009']; $pOil->refresh();
                        if ($pOil->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $pOil->id, 1, null, $admin->id);
                    } elseif ($service->code === 'SRV-007') {
                        $pOil = $parts['SP-0012']; $pOil->refresh();
                        if ($pOil->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $pOil->id, 1, null, $admin->id);
                        $pHose = $parts['SP-0016']; $pHose->refresh();
                        if ($pHose->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $pHose->id, 1, null, $admin->id);
                    } elseif (in_array($service->code, ['SRV-004', 'SRV-006'], true)) {
                        $pOil = $parts['SP-0009']; $pOil->refresh();
                        if ($pOil->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $pOil->id, 1, null, $admin->id);
                        $pFil = $parts['SP-0001']; $pFil->refresh();
                        if ($pFil->qty_on_hand >= 2) $this->workOrderService->addPartItem($wo, $pFil->id, 2, null, $admin->id);
                    } elseif (in_array($service->code, ['SRV-003', 'SRV-009'], true)) {
                        $pOil = $parts['SP-0012']; $pOil->refresh();
                        if ($pOil->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $pOil->id, 1, null, $admin->id);
                        $pFil = $parts['SP-0002']; $pFil->refresh();
                        if ($pFil->qty_on_hand >= 2) $this->workOrderService->addPartItem($wo, $pFil->id, 2, null, $admin->id);
                    } else {
                        $pOil = $parts['SP-0010']; $pOil->refresh();
                        if ($pOil->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $pOil->id, 1, null, $admin->id);
                        $pFil = $parts['SP-0001']; $pFil->refresh();
                        if ($pFil->qty_on_hand >= 1) $this->workOrderService->addPartItem($wo, $pFil->id, 1, null, $admin->id);
                    }
                });

                // 2. Transisi Status
                if ($isCancelled) {
                    $this->schedule($timeline, $woTime->copy()->addHours(2), function () use ($woIndex, &$workOrders, &$users) {
                        $this->workOrderService->transitionStatus($workOrders[$woIndex], 'cancelled', $users['admin']->id);
                    });
                    continue;
                }

                if ($isOpen) {
                    continue; // Tetap open
                }

                $this->schedule($timeline, $woTime->copy()->addHours(2), function () use ($woIndex, &$workOrders, &$users) {
                    $this->workOrderService->transitionStatus($workOrders[$woIndex], 'in_progress', $users['mechanics'][0]->id);
                });

                if ($isInProgress) {
                    continue; // Tetap in_progress
                }

                $this->schedule($timeline, $woTime->copy()->addHours(5), function () use ($woIndex, &$workOrders, &$users) {
                    $this->workOrderService->transitionStatus($workOrders[$woIndex], 'completed', $users['mechanics'][0]->id);
                });

                if ($isCompletedUninvoiced) {
                    continue; // Tetap completed tanpa faktur
                }

                // 3. Penerbitan Faktur (Invoice)
                $invTime = $woTime->copy()->addDay()->setTime(9, 0)->min($maxSimDate);
                $giveDiscount = ($discountTargetCount < 3);
                if ($giveDiscount) $discountTargetCount++;

                $this->schedule($timeline, $invTime, function () use ($woIndex, &$workOrders, &$woInvoices, $invTime, $giveDiscount, &$users) {
                    $woInvoices[$woIndex] = $this->invoiceService->createFromWorkOrder($workOrders[$woIndex], [
                        'issue_date' => $invTime->toDateString(),
                        'discount_amount' => $giveDiscount ? 50000 : 0,
                    ], $users['cashier']->id);
                });

                // 4. Pembayaran Faktur Order Servis
                $woMod = $woIndex % 10;
                $isInstallment = false;
                if ($woMod !== 0 && $woMod !== 1 && $woMod !== 2 && $installmentTargetCount < 6) {
                    $isInstallment = true;
                    $installmentTargetCount++;
                }

                if ($woMod === 0) {
                    // Belum dibayar (issued)
                } elseif ($woMod === 1 || $woMod === 2) {
                    // Sebagian (~45%)
                    $pTime = $invTime->copy()->addDays(3)->setTime(11, 0)->min($maxSimDate);
                    $this->schedule($timeline, $pTime, function () use ($woIndex, &$woInvoices, $pTime, &$users) {
                        if (isset($woInvoices[$woIndex])) {
                            $inv = $woInvoices[$woIndex];
                            $inv->refresh();
                            if ($inv->status !== 'paid' && $inv->status !== 'void') {
                                $partial = (int) round($inv->total * 0.45, -3);
                                $partial = min($partial, $inv->remainingBalance());
                                if ($partial > 0) {
                                    $this->paymentService->recordPayment($inv, [
                                        'amount' => $partial,
                                        'paid_at' => $pTime->toDateString(),
                                        'method' => 'transfer',
                                        'reference' => 'PAY-WO-PART-' . $inv->id,
                                    ], $users['cashier']->id);
                                }
                            }
                        }
                    });
                } elseif ($isInstallment) {
                    // 3 Kali Cicilan
                    $pTime1 = $invTime->copy()->addDays(1)->setTime(10, 0)->min($maxSimDate);
                    $pTime2 = $invTime->copy()->addDays(3)->setTime(10, 0)->min($maxSimDate);
                    $pTime3 = $invTime->copy()->addDays(5)->setTime(10, 0)->min($maxSimDate);

                    $this->schedule($timeline, $pTime1, function () use ($woIndex, &$woInvoices, $pTime1, &$users) {
                        if (isset($woInvoices[$woIndex])) {
                            $inv = $woInvoices[$woIndex];
                            $inv->refresh();
                            if ($inv->status !== 'paid' && $inv->status !== 'void') {
                                $p1 = (int) round($inv->total * 0.4, -3);
                                $p1 = min($p1, $inv->remainingBalance());
                                if ($p1 > 0) {
                                    $this->paymentService->recordPayment($inv, ['amount' => $p1, 'paid_at' => $pTime1->toDateString(), 'method' => 'transfer'], $users['cashier']->id);
                                }
                            }
                        }
                    });

                    $this->schedule($timeline, $pTime2, function () use ($woIndex, &$woInvoices, $pTime2, &$users) {
                        if (isset($woInvoices[$woIndex])) {
                            $inv = $woInvoices[$woIndex];
                            $inv->refresh();
                            if ($inv->status !== 'paid' && $inv->status !== 'void') {
                                $p2 = (int) round($inv->total * 0.3, -3);
                                $p2 = min($p2, $inv->remainingBalance());
                                if ($p2 > 0) {
                                    $this->paymentService->recordPayment($inv, ['amount' => $p2, 'paid_at' => $pTime2->toDateString(), 'method' => 'transfer'], $users['cashier']->id);
                                }
                            }
                        }
                    });

                    $this->schedule($timeline, $pTime3, function () use ($woIndex, &$woInvoices, $pTime3, &$users) {
                        if (isset($woInvoices[$woIndex])) {
                            $inv = $woInvoices[$woIndex];
                            $inv->refresh();
                            if ($inv->status !== 'paid' && $inv->status !== 'void') {
                                $rem = $inv->remainingBalance();
                                if ($rem > 0) {
                                    $this->paymentService->recordPayment($inv, ['amount' => $rem, 'paid_at' => $pTime3->toDateString(), 'method' => 'cash'], $users['cashier']->id);
                                }
                            }
                        }
                    });
                } else {
                    // Lunas
                    $pTime = $invTime->copy()->addDays(rand(1, 4))->setTime(14, 0)->min($maxSimDate);
                    $this->schedule($timeline, $pTime, function () use ($woIndex, &$woInvoices, $pTime, &$users) {
                        if (isset($woInvoices[$woIndex])) {
                            $inv = $woInvoices[$woIndex];
                            $inv->refresh();
                            if ($inv->status !== 'paid' && $inv->status !== 'void') {
                                $rem = $inv->remainingBalance();
                                if ($rem > 0) {
                                    $this->paymentService->recordPayment($inv, [
                                        'amount' => $rem,
                                        'paid_at' => $pTime->toDateString(),
                                        'method' => 'transfer',
                                        'reference' => 'PAY-WO-FULL-' . $inv->id,
                                    ], $users['cashier']->id);
                                }
                            }
                        }
                    });
                }
            }

            // Jadwalkan Counter Sales (Penjualan Toko Langsung)
            for ($c = 0; $c < $csInMonth; $c++) {
                $globalCs++;
                $csIndex = $globalCs;
                $day = min($maxDay, max(1, (int) round(($c + 1) * ($maxDay / ($csInMonth + 1)))));
                $csTime = Carbon::create($y, $mo, $day, 11, 0);

                // Void 3 counter sales: CS #15 (bulan 4), CS #32 (bulan 7), CS #48 (bulan 10)
                $isVoid = ($csIndex === 15 || $csIndex === 32 || $csIndex === 48);

                $this->schedule($timeline, $csTime, function () use ($csIndex, $c, &$csInvoices, $csTime, &$customers, &$parts, &$users) {
                    $customersList = array_values($customers);
                    $customer = ($c % 2 === 0) ? $customers['CUST-UMUM'] : $customersList[($csIndex * 3) % count($customersList)];

                    // Batasan transaksi toko: part > 1M maks qty 10; part <= 1M maks qty 30; invoice total <= 30M
                    $saleItems = [];
                    if ($c === 0) {
                        $pOil = $parts['SP-0009']; $pOil->refresh();
                        if ($pOil->qty_on_hand >= 1) $saleItems[] = ['part_id' => $pOil->id, 'qty' => 1];
                        $pFil = $parts['SP-0001']; $pFil->refresh();
                        if ($pFil->qty_on_hand >= 4) $saleItems[] = ['part_id' => $pFil->id, 'qty' => 4];
                    } elseif ($c === 1) {
                        $pOil2 = $parts['SP-0012']; $pOil2->refresh();
                        if ($pOil2->qty_on_hand >= 1) $saleItems[] = ['part_id' => $pOil2->id, 'qty' => 1];
                        $pHos = $parts['SP-0016']; $pHos->refresh();
                        if ($pHos->qty_on_hand >= 2) $saleItems[] = ['part_id' => $pHos->id, 'qty' => 2];
                    } else {
                        $part = $parts['SP-0010']; // Oli pail
                        $part->refresh();
                        if ($part->qty_on_hand >= 2) $saleItems[] = ['part_id' => $part->id, 'qty' => 2];
                        $pFil = $parts['SP-0002'];
                        $pFil->refresh();
                        if ($pFil->qty_on_hand >= 2) $saleItems[] = ['part_id' => $pFil->id, 'qty' => 2];
                    }

                    $csInvoices[$csIndex] = $this->invoiceService->createDirectSale([
                        'customer_id' => $customer->id,
                        'issue_date' => $csTime->toDateString(),
                        'discount_amount' => 0,
                        'items' => $saleItems,
                    ], $users['cashier']->id);
                });

                if ($isVoid) {
                    $voidTime = $csTime->copy()->addDay()->setTime(15, 0)->min($maxSimDate);
                    $this->schedule($timeline, $voidTime, function () use ($csIndex, &$csInvoices, &$users) {
                        if (isset($csInvoices[$csIndex])) {
                            $this->invoiceService->voidInvoice(
                                invoice: $csInvoices[$csIndex],
                                reason: 'Koreksi faktur ganda penjualan toko',
                                userId: $users['admin']->id
                            );
                        }
                    });
                } else {
                    $csPayTime = $csTime->copy()->addHours(2)->min($maxSimDate);
                    $this->schedule($timeline, $csPayTime, function () use ($csIndex, &$csInvoices, $csPayTime, &$users) {
                        if (isset($csInvoices[$csIndex])) {
                            $inv = $csInvoices[$csIndex];
                            $inv->refresh();
                            if ($inv->status !== 'paid' && $inv->status !== 'void') {
                                $rem = $inv->remainingBalance();
                                if ($rem > 0) {
                                    $this->paymentService->recordPayment($inv, [
                                        'amount' => $rem,
                                        'paid_at' => $csPayTime->toDateString(),
                                        'method' => 'cash',
                                        'reference' => 'PAY-CS-' . $inv->id,
                                    ], $users['cashier']->id);
                                }
                            }
                        }
                    });
                }
            }
        }

        // --- EVENT 7: Penjualan Kalibrasi Stok Menipis (2026-10-19 14:00:00) ---
        $this->schedule($timeline, Carbon::parse('2026-10-19 14:00:00'), function () use (&$parts, &$customers, &$users) {
            // SP-0020 menjadi 0
            $rod = $parts['SP-0020']; $rod->refresh();
            if ($rod->qty_on_hand > 0) {
                $this->invoiceService->createDirectSale([
                    'customer_id' => $customers['CUST-BTP']->id,
                    'issue_date' => '2026-10-19',
                    'discount_amount' => 0,
                    'items' => [['part_id' => $rod->id, 'qty' => min(10, $rod->qty_on_hand)]],
                ], $users['cashier']->id);
            }

            // SP-0022, 0027, 0034, 0038 <= min_stock
            $lowSkus = ['SP-0022', 'SP-0027', 'SP-0034', 'SP-0038'];
            foreach ($lowSkus as $sku) {
                $p = $parts[$sku]; $p->refresh();
                if ($p->qty_on_hand > $p->min_stock) {
                    $diff = $p->qty_on_hand - $p->min_stock;
                    $this->invoiceService->createDirectSale([
                        'customer_id' => $customers['CUST-NRA']->id,
                        'issue_date' => '2026-10-19',
                        'discount_amount' => 0,
                        'items' => [['part_id' => $p->id, 'qty' => min(10, $diff)]],
                    ], $users['cashier']->id);
                }
            }
        });

        // --- EVENT 8: Pembatasan Dinamis Konsentrasi Piutang Customer <= 25% (2026-10-20 16:30:00) ---
        $this->schedule($timeline, Carbon::parse('2026-10-20 16:30:00'), function () use (&$users) {
            $cashier = $users['cashier'];
            $maxDate = Carbon::parse('2026-10-20');

            $maxIterations = 20;
            do {
                $recAfter = $this->reportService->getReceivablesReport('2026-10-20');
                $totalRecAfter = (int) $recAfter['total_receivables'];
                $hasViolation = false;

                foreach (Customer::all() as $c) {
                    $cRec = (int) $c->calculateTotalReceivables();
                    $share = $totalRecAfter > 0 ? ($cRec / $totalRecAfter) : 0;

                    if ($share > 0.245 && $totalRecAfter > 0) {
                        $hasViolation = true;
                        $unpaid = $c->invoices()->where('status', '!=', 'void')->with('validPayments')->get()->first(fn ($inv) => $inv->remainingBalance() > 0);
                        if ($unpaid) {
                            $rem = $unpaid->remainingBalance();
                            $excess = (int) ($cRec - ($totalRecAfter * 0.20));
                            $payAmount = max(500000, min($rem, $excess));
                            $pDate = Carbon::parse($unpaid->issue_date)->addDays(5)->min($maxDate);
                            Carbon::setTestNow($pDate);
                            $this->paymentService->recordPayment($unpaid, ['amount' => $payAmount, 'paid_at' => $pDate->toDateString(), 'method' => 'transfer', 'reference' => 'CAP-ADJ'], $cashier->id);
                            Carbon::setTestNow(Carbon::parse('2026-10-20 16:30:00'));
                        }
                    }
                }
                $maxIterations--;
            } while ($hasViolation && $maxIterations > 0);
        });
    }

    /**
     * Verifikasi Semua Invarian Bisnis (Wajib SEED_SPEC + Fase E).
     * Jika ada yang tidak terpenuhi, lempar Exception (seeding gagal).
     */
    public function assertAllInvariants(): void
    {
        // Invarian 1: Tidak ada stok minus; qty_on_hand = Σ movements untuk setiap part (BR-S1)
        $allParts = Part::all();
        if ($allParts->count() < 45) {
            throw new \RuntimeException("Pelanggaran: Jumlah sparepart ({$allParts->count()}) kurang dari 45.");
        }

        foreach ($allParts as $part) {
            if ($part->qty_on_hand < 0) {
                throw new \RuntimeException("Pelanggaran Invarian 1: Stok minus terdeteksi pada part {$part->sku} ({$part->qty_on_hand})");
            }

            $movementSum = (int) StockMovement::where('part_id', $part->id)->sum('qty_change');
            if ($part->qty_on_hand !== $movementSum) {
                throw new \RuntimeException(
                    "Pelanggaran Invarian 1 (BR-S1): qty_on_hand ({$part->qty_on_hand}) != Σ movements ({$movementSum}) pada part {$part->sku}"
                );
            }
        }

        // Invarian 2: harga_jual > avg_cost untuk semua part aktif (margin positif)
        foreach ($allParts as $part) {
            if ($part->is_active && $part->sell_price <= $part->avg_cost) {
                throw new \RuntimeException(
                    "Pelanggaran Invarian 2: Harga jual ({$part->sell_price}) <= avg_cost ({$part->avg_cost}) pada part {$part->sku}"
                );
            }
        }

        // Invarian 3 & 4: Setiap invoice valid, total > 0, pembayaran <= total, dan tidak ada pembayaran invoice void
        $allInvoices = Invoice::with(['items', 'validPayments'])->get();
        if ($allInvoices->count() < 160) {
            throw new \RuntimeException("Pelanggaran: Total invoice ({$allInvoices->count()}) kurang dari 160.");
        }

        $voidCount = 0;
        $discountedCount = 0;
        foreach ($allInvoices as $inv) {
            // Formula invoice (BR-I2)
            $expectedTotal = (int) $inv->subtotal - (int) $inv->discount_amount + (int) $inv->tax_amount;
            if ($inv->total !== $expectedTotal) {
                throw new \RuntimeException(
                    "Pelanggaran Invarian 3: Total invoice {$inv->invoice_number} ({$inv->total}) != subtotal - diskon + pajak ({$expectedTotal})"
                );
            }

            if ($inv->total <= 0) {
                throw new \RuntimeException("Pelanggaran Invarian 4: Ditemukan invoice dengan total <= 0: {$inv->invoice_number}");
            }

            $totalPaid = (int) $inv->validPayments->sum('amount');
            if ($totalPaid > $inv->total) {
                throw new \RuntimeException(
                    "Pelanggaran Invarian 3: Pembayaran ({$totalPaid}) melebihi total ({$inv->total}) pada invoice {$inv->invoice_number}"
                );
            }

            if ($inv->status === 'void') {
                $voidCount++;
                if ($inv->validPayments()->count() > 0) {
                    throw new \RuntimeException(
                        "Pelanggaran Invarian 4: Invoice void {$inv->invoice_number} memiliki pembayaran valid!"
                    );
                }
            }

            if ($inv->discount_amount > 0) {
                $discountedCount++;
            }
        }

        if ($voidCount !== 3) {
            throw new \RuntimeException("Pelanggaran Variasi: Ditemukan {$voidCount} invoice void, harus persis 3 (SEED_SPEC).");
        }

        if ($discountedCount < 3) {
            throw new \RuntimeException("Pelanggaran Variasi: Ditemukan {$discountedCount} invoice dengan diskon, harus minimal 3.");
        }

        // Invarian 5: Total piutang di semua cara hitung IDENTIK (BR-R8)
        $todayStr = '2026-10-20';
        $reportReceivables = $this->reportService->getReceivablesReport($todayStr)['total_receivables'];
        $dashboardReceivables = $this->reportService->getDashboardSummary()['total_receivables'];

        $sumCustomersReceivables = 0;
        foreach (Customer::all() as $cust) {
            $sumCustomersReceivables += $cust->calculateTotalReceivables();
        }

        if ($reportReceivables !== $dashboardReceivables) {
            throw new \RuntimeException(
                "Pelanggaran Invarian 5 (BR-R8): Total piutang di laporan ({$reportReceivables}) != di dashboard ({$dashboardReceivables})"
            );
        }

        if ($reportReceivables !== $sumCustomersReceivables) {
            throw new \RuntimeException(
                "Pelanggaran Invarian 5 (BR-R8): Total piutang di laporan ({$reportReceivables}) != Σ per customer ({$sumCustomersReceivables})"
            );
        }

        // Invarian 6: Tanggal pembayaran >= tanggal invoice; tanggal invoice >= tanggal order
        $payments = Payment::with('invoice.workOrder')->where('status', 'valid')->get();
        if ($payments->count() < 100) {
            throw new \RuntimeException("Pelanggaran: Jumlah pembayaran valid ({$payments->count()}) kurang dari 100.");
        }

        $installmentInvoices = [];
        foreach ($payments as $p) {
            if ($p->paid_at->lt($p->invoice->issue_date)) {
                throw new \RuntimeException(
                    "Pelanggaran Invarian 6: Tanggal bayar ({$p->paid_at->toDateString()}) < tanggal invoice ({$p->invoice->issue_date->toDateString()})"
                );
            }

            if ($p->invoice->workOrder && $p->invoice->issue_date->lt($p->invoice->workOrder->opened_at->toDateString())) {
                throw new \RuntimeException(
                    "Pelanggaran Invarian 6: Tanggal invoice ({$p->invoice->issue_date->toDateString()}) < tanggal order dibuka ({$p->invoice->workOrder->opened_at->toDateString()})"
                );
            }

            $installmentInvoices[$p->invoice_id] = ($installmentInvoices[$p->invoice_id] ?? 0) + 1;
        }

        $multiPaymentCount = count(array_filter($installmentInvoices, fn ($cnt) => $cnt >= 2));
        if ($multiPaymentCount < 5) {
            throw new \RuntimeException("Pelanggaran Variasi: Hanya ditemukan {$multiPaymentCount} invoice dengan cicilan (minimal 5).");
        }

        // Variasi Stok Menipis
        $lowStockCount = Part::where('is_active', true)->whereColumn('qty_on_hand', '<=', 'min_stock')->count();
        $zeroStockCount = Part::where('is_active', true)->where('qty_on_hand', 0)->count();
        if ($lowStockCount < 4) {
            throw new \RuntimeException("Pelanggaran Variasi: Part dengan stok <= min_stock ({$lowStockCount}) kurang dari 4.");
        }
        if ($zeroStockCount !== 1) {
            throw new \RuntimeException("Pelanggaran Variasi: Part dengan stok 0 persis ({$zeroStockCount}) harus persis 1 (SP-0020).");
        }

        // Variasi Work Order Status
        if (WorkOrder::where('status', 'open')->count() !== 2) throw new \RuntimeException("Pelanggaran: WO open harus persis 2.");
        if (WorkOrder::where('status', 'in_progress')->count() !== 2) throw new \RuntimeException("Pelanggaran: WO in_progress harus persis 2.");
        if (WorkOrder::where('status', 'completed')->count() !== 3) throw new \RuntimeException("Pelanggaran: WO completed harus persis 3.");
        if (WorkOrder::where('status', 'cancelled')->count() !== 2) throw new \RuntimeException("Pelanggaran: WO cancelled harus persis 2.");

        // Tarif Khusus Jasa
        $ratesCount = CustomerServiceRate::distinct('customer_id')->count('customer_id');
        if ($ratesCount !== 3) {
            throw new \RuntimeException("Pelanggaran: Harus ada persis 3 customer dengan tarif khusus ({$ratesCount} ditemukan).");
        }

        // --- TAMBAHAN INVARIAN FASE E ---
        // 1. Akun Pengguna Wajib
        $expectedUsers = ['admin@bengkel.com', 'kasir@bengkel.com', 'mekanik1@bengkel.com', 'mekanik2@bengkel.com'];
        $actualUsers = User::where('is_active', true)->pluck('email')->toArray();
        sort($expectedUsers);
        sort($actualUsers);
        if ($expectedUsers !== $actualUsers) {
            throw new \RuntimeException("Pelanggaran Fase E: Akun demo tidak sesuai dengan README. Ditemukan: " . implode(', ', $actualUsers));
        }

        // 2. Tidak Ada Timestamp di Masa Depan
        $anchor = Carbon::parse('2026-10-20 23:59:59');
        $futureChecks = [
            'stock_movements' => StockMovement::where('created_at', '>', $anchor)->count(),
            'invoices' => Invoice::where('created_at', '>', $anchor)->orWhere('issue_date', '>', '2026-10-20')->count(),
            'payments' => Payment::where('created_at', '>', $anchor)->orWhere('paid_at', '>', '2026-10-20')->count(),
            'work_orders' => WorkOrder::where('created_at', '>', $anchor)->orWhere('opened_at', '>', $anchor)->count(),
            'purchases' => Purchase::where('created_at', '>', $anchor)->orWhere('purchase_date', '>', '2026-10-20')->count(),
            'expenses' => Expense::where('created_at', '>', $anchor)->orWhere('expense_date', '>', '2026-10-20')->count(),
            'audit_logs' => AuditLog::where('created_at', '>', $anchor)->count(),
        ];
        foreach ($futureChecks as $tbl => $cnt) {
            if ($cnt > 0) {
                throw new \RuntimeException("Pelanggaran Fase E: Ditemukan {$cnt} record di masa depan pada tabel {$tbl}.");
            }
        }

        // 3. Kartu Stok Urut Kronologis
        foreach ($allParts as $part) {
            $movements = StockMovement::where('part_id', $part->id)->orderBy('id')->get();
            $prevTime = null;
            foreach ($movements as $m) {
                if ($prevTime && $m->created_at->lt($prevTime)) {
                    throw new \RuntimeException(
                        "Pelanggaran Fase E: Inversi kronologis pada kartu stok part {$part->sku} (id {$m->id} {$m->created_at} < {$prevTime})"
                    );
                }
                $prevTime = $m->created_at;
            }
        }

        // 4. Batasan Transaksi Penjualan Toko Langsung
        $counterSales = Invoice::where('source_type', 'counter_sale')->with('items.part')->get();
        foreach ($counterSales as $cs) {
            if ($cs->total > 30000000) {
                throw new \RuntimeException("Pelanggaran Fase E: Total invoice penjualan toko {$cs->invoice_number} melebihi Rp 30.000.000 ({$cs->total})");
            }
            foreach ($cs->items as $it) {
                $price = $it->part ? $it->part->sell_price : $it->unit_price;
                if ($price > 1000000 && $it->qty > 10) {
                    throw new \RuntimeException("Pelanggaran Fase E: Qty item mahal > Rp 1.000.000 pada invoice {$cs->invoice_number} melebihi 10 unit ({$it->qty})");
                }
                if ($price <= 1000000 && $it->qty > 30) {
                    throw new \RuntimeException("Pelanggaran Fase E: Qty item murah pada invoice {$cs->invoice_number} melebihi 30 unit ({$it->qty})");
                }
            }
        }

        // 5. Konsentrasi Piutang Maksimal 25% per Customer
        $recData = $this->reportService->getReceivablesReport('2026-10-20');
        $totalRec = (int) $recData['total_receivables'];
        if ($totalRec > 0) {
            foreach (Customer::all() as $c) {
                $cRec = (int) $c->calculateTotalReceivables();
                $share = $cRec / $totalRec;
                if ($share > 0.25) {
                    $sharePercent = round($share * 100, 2);
                    throw new \RuntimeException("Pelanggaran Fase E: Customer {$c->name} menguasai {$sharePercent}% piutang (maksimal 25%).");
                }
            }
        }

        // 6. Omzet Bulanan Rentang Rp 80.000.000 s.d. Rp 250.000.000 Selama 12 Bulan
        for ($i = 11; $i >= 0; $i--) {
            $d = Carbon::parse('2026-10-20')->subMonths($i);
            $startStr = $d->copy()->startOfMonth()->toDateString();
            $endStr = ($i === 0) ? '2026-10-20' : $d->copy()->endOfMonth()->toDateString();
            $rev = (int) Invoice::where('status', '!=', 'void')
                ->whereDate('issue_date', '>=', $startStr)
                ->whereDate('issue_date', '<=', $endStr)
                ->sum('total');

            if ($rev < 80000000 || $rev > 250000000) {
                $monthStr = $d->format('Y-m');
                throw new \RuntimeException("Pelanggaran Fase E: Omzet bulan {$monthStr} (Rp " . number_format($rev, 0, ',', '.') . ") di luar rentang Rp 80-250 juta.");
            }
        }

        // 7. Semua Bucket Aging Piutang Terisi (> 0)
        $agingBuckets = ['not_due', 'days_1_30', 'days_31_60', 'days_61_90', 'days_over_90'];
        foreach ($agingBuckets as $b) {
            if (($recData['aging'][$b] ?? 0) <= 0) {
                throw new \RuntimeException("Pelanggaran Fase E: Bucket umur piutang {$b} bernilai 0 (harus terisi).");
            }
        }
    }

    /**
     * Cetak Ringkasan Angka Akhir (SEED_SPEC & Fase E).
     */
    protected function printSummary(): void
    {
        $this->newLine();
        $this->line("==========================================================================");
        $this->line("             RINGKASAN DATA SEEDING DEMO (SEED_SPEC - FASE E)             ");
        $this->line("==========================================================================");

        // 1. Jumlah per tabel
        $this->table(['Nama Tabel Data', 'Jumlah Baris'], [
            ['Users', User::count()],
            ['Customers', Customer::count()],
            ['Equipment Units', EquipmentUnit::count()],
            ['Services (Jasa)', Service::count()],
            ['Parts (Sparepart)', Part::count()],
            ['Suppliers', Supplier::count()],
            ['Purchases (Barang Masuk)', Purchase::count()],
            ['Work Orders (Order Servis)', WorkOrder::count()],
            ['Invoices (Faktur)', Invoice::count()],
            ['Payments (Pembayaran)', Payment::count()],
            ['Expenses (Pengeluaran)', Expense::count()],
            ['Stock Movements (Kartu Stok)', StockMovement::count()],
            ['Audit Logs', AuditLog::count()],
        ]);

        // 2. Metrik Finansial 12 Bulan
        $startDate = '2025-11-01';
        $endDate = '2026-10-31';

        $pl = $this->reportService->getProfitLossReport($startDate, $endDate);
        $rec = $this->reportService->getReceivablesReport('2026-10-20');

        $this->table(['Metrik Laporan Finansial (12 Bulan)', 'Nominal (Rupiah)'], [
            ['Omzet Penjualan (BR-R1)', 'Rp ' . number_format($pl['total_revenue'], 0, ',', '.')],
            ['Harga Pokok Penjualan - HPP (BR-R2)', 'Rp ' . number_format($pl['total_hpp'], 0, ',', '.')],
            ['Laba Kotor (BR-R3: Omzet - HPP)', 'Rp ' . number_format($pl['gross_profit'], 0, ',', '.')],
            ['Total Beban Operasional', 'Rp ' . number_format($pl['total_expenses'], 0, ',', '.')],
            ['Laba Bersih Sederhana (BR-R3)', 'Rp ' . number_format($pl['net_profit'], 0, ',', '.')],
            ['Total Piutang Berjalan (BR-R6, BR-R8)', 'Rp ' . number_format($rec['total_receivables'], 0, ',', '.')],
        ]);

        // 3. Omzet Bulanan (12 Bulan)
        $monthlyRevenueRows = [];
        for ($i = 11; $i >= 0; $i--) {
            $d = Carbon::parse('2026-10-20')->subMonths($i);
            $startStr = $d->copy()->startOfMonth()->toDateString();
            $endStr = ($i === 0) ? '2026-10-20' : $d->copy()->endOfMonth()->toDateString();
            $rev = (int) Invoice::where('status', '!=', 'void')
                ->whereDate('issue_date', '>=', $startStr)
                ->whereDate('issue_date', '<=', $endStr)
                ->sum('total');

            $monthlyRevenueRows[] = [
                $d->format('Y-m') . ' (' . $d->translatedFormat('F Y') . ')',
                'Rp ' . number_format($rev, 0, ',', '.'),
                ($rev >= 80000000 && $rev <= 250000000) ? 'Sesuai Target (Rp 80M - 250M)' : 'Di Luar Target',
            ];
        }
        $this->table(['Bulan Transaksi', 'Omzet Penjualan', 'Status Target'], $monthlyRevenueRows);

        // 4. Piutang per Customer & Porsi Konsentrasi (Maks 25%)
        $customerRecRows = [];
        $totalRec = (int) $rec['total_receivables'];
        foreach (Customer::all() as $cust) {
            $cRec = (int) $cust->calculateTotalReceivables();
            $share = $totalRec > 0 ? ($cRec / $totalRec) : 0;
            $customerRecRows[] = [
                $cust->code . ' - ' . $cust->name,
                'Rp ' . number_format($cRec, 0, ',', '.'),
                number_format($share * 100, 1) . '%',
                $share <= 0.25 ? 'Aman (<= 25%)' : 'Melebihi Batas',
            ];
        }
        $this->table(['Customer', 'Total Piutang', 'Porsi', 'Status Konsentrasi'], $customerRecRows);

        // 5. Status Invoice
        $this->table(['Status Invoice', 'Jumlah'], [
            ['Lunas (paid)', Invoice::where('status', 'paid')->count()],
            ['Dibayar Sebagian (partially_paid)', Invoice::where('status', 'partially_paid')->count()],
            ['Belum Dibayar (issued)', Invoice::where('status', 'issued')->count()],
            ['Dibatalkan (void)', Invoice::where('status', 'void')->count()],
            ['TOTAL SEMUA INVOICE', Invoice::count()],
        ]);

        // 6. Distribusi Aging Piutang
        $this->table(['Distribusi Umur Piutang (BR-R7)', 'Nominal'], [
            ['Belum Jatuh Tempo (<=0)', 'Rp ' . number_format($rec['aging']['not_due'], 0, ',', '.')],
            ['1 - 30 Hari', 'Rp ' . number_format($rec['aging']['days_1_30'], 0, ',', '.')],
            ['31 - 60 Hari', 'Rp ' . number_format($rec['aging']['days_31_60'], 0, ',', '.')],
            ['61 - 90 Hari', 'Rp ' . number_format($rec['aging']['days_61_90'], 0, ',', '.')],
            ['> 90 Hari (Tunggakan Macet)', 'Rp ' . number_format($rec['aging']['days_over_90'], 0, ',', '.')],
        ]);

        // 7. Peringatan Stok
        $lowStockCount = Part::where('is_active', true)->whereColumn('qty_on_hand', '<=', 'min_stock')->count();
        $zeroStockCount = Part::where('is_active', true)->where('qty_on_hand', 0)->count();

        $this->line("Peringatan Stok Menipis: {$lowStockCount} part <= batas minimum (1 part dengan stok 0 persis)");
        $this->line("==========================================================================");
        $this->newLine();
    }
}
