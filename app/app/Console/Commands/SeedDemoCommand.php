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
use App\Models\Service;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
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
    protected $description = 'Seed data demonstrasi realistis dan deterministik sesuai docs/SEED_SPEC.md';

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
        $this->info("=== Memulai Seeding Data Demonstrasi ERP Bengkel (SEED_SPEC.md) ===");

        // 1. Database bersih (--fresh selalu dijalankan agar deterministik dan dapat diulang)
        $this->info("Menjalankan migrate:fresh untuk memastikan database bersih...");
        Artisan::call('migrate:fresh', ['--force' => true]);

        // Optimasi SQLite WAL untuk eksekusi batch cepat
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA journal_mode=WAL;');
            DB::statement('PRAGMA synchronous=NORMAL;');
        }

        // 2. Deterministik: Seed angka acak dan anchor tanggal tetap
        mt_srand(42);
        srand(42);

        $baseDate = Carbon::parse('2026-10-20 12:00:00');
        Carbon::setTestNow($baseDate);

        // 3. Seed Pengaturan & User
        $users = $this->seedUsersAndSettings();
        $admin = $users['admin'];
        $cashier = $users['cashier'];
        $mechanics = $users['mechanics'];

        // 4. Seed Master Customer & Unit Alat Berat
        $customers = $this->seedCustomers();
        $units = $this->seedEquipmentUnits($customers);

        // 5. Seed Jasa & Tarif Khusus Customer
        $services = $this->seedServices($customers);

        // 6. Seed Supplier & Sparepart dengan Pembelian Awal
        $suppliers = $this->seedSuppliers();
        $parts = $this->seedPartsAndPurchases($suppliers, $admin);

        // 7. Seed Penyesuaian Stok (BR-S6)
        $this->seedStockAdjustments($parts, $admin);

        // 8. Seed Pengeluaran Operasional (±40 transaksi)
        $this->seedExpenses($admin);

        // 9. Seed Order Servis (±110) & Penjualan Langsung (±60) dengan Alur Nyata
        $this->seedTransactions($customers, $units, $services, $parts, $admin, $cashier, $mechanics);

        // 10. Validasi Invarian (Wajib: gagal = seeding gagal)
        $this->info("Memverifikasi semua invarian bisnis...");
        $this->assertAllInvariants();
        $this->info("✓ Seluruh invarian berhasil diverifikasi tanpa pelanggaran!");

        // 11. Cetak Ringkasan Angka Akhir
        $this->printSummary();

        Carbon::setTestNow(); // Kembalikan jam sistem

        $this->info("=== Seeding Data Demo Selesai dengan Sukses! ===");
        return Command::SUCCESS;
    }

    /**
     * Seed User dan Pengaturan Dasar.
     */
    protected function seedUsersAndSettings(): array
    {
        Setting::set('shop_name', 'Bengkel Alat Berat & Toko Sparepart Nusantara');
        Setting::set('tax_rate_percent', 0); // Default pajak 0 (BR-I3)

        $admin = User::create([
            'name' => 'Administrator Demo',
            'email' => 'admin@erp.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $cashier = User::create([
            'name' => 'Siti Kasir',
            'email' => 'kasir@erp.test',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $m1 = User::create([
            'name' => 'Budi Mekanik Senior',
            'email' => 'mekanik1@erp.test',
            'password' => Hash::make('password'),
            'role' => 'mechanic',
            'is_active' => true,
        ]);

        $m2 = User::create([
            'name' => 'Agus Mekanik Hidrolik',
            'email' => 'mekanik2@erp.test',
            'password' => Hash::make('password'),
            'role' => 'mechanic',
            'is_active' => true,
        ]);

        return [
            'admin' => $admin,
            'cashier' => $cashier,
            'mechanics' => [$m1, $m2],
        ];
    }

    /**
     * Seed 10 Customer + Pelanggan Umum.
     */
    protected function seedCustomers(): array
    {
        $customersData = [
            ['code' => 'CUST-UMUM', 'name' => 'Pelanggan Umum', 'type' => 'individual', 'payment_term_days' => 0, 'phone' => '-'],
            ['code' => 'CUST-BTP', 'name' => 'PT Borneo Tambang Pratama', 'type' => 'company', 'payment_term_days' => 30, 'phone' => '0811550101'],
            ['code' => 'CUST-MMM', 'name' => 'PT Mahakam Mineral Mandiri', 'type' => 'company', 'payment_term_days' => 45, 'phone' => '0811550102'], // Tunggakan >90 hari
            ['code' => 'CUST-ALP', 'name' => 'PT Andalas Logistik Perkasa', 'type' => 'company', 'payment_term_days' => 14, 'phone' => '0811550103'],
            ['code' => 'CUST-NRA', 'name' => 'CV Nusantara Rental Alat', 'type' => 'company', 'payment_term_days' => 30, 'phone' => '0811550104'],
            ['code' => 'CUST-SKS', 'name' => 'PT Samudra Karya Sejahtera', 'type' => 'company', 'payment_term_days' => 30, 'phone' => '0811550105'],
            ['code' => 'CUST-JKS', 'name' => 'Bapak Joko Susilo', 'type' => 'individual', 'payment_term_days' => 14, 'phone' => '0812340106'], // Piutang ringan
            ['code' => 'CUST-HRH', 'name' => 'Haji Rahmat Hidayat', 'type' => 'individual', 'payment_term_days' => 0, 'phone' => '0812340107'],
            ['code' => 'CUST-HDK', 'name' => 'Bapak Hendra Kusuma', 'type' => 'individual', 'payment_term_days' => 14, 'phone' => '0812340108'], // Piutang ringan
            ['code' => 'CUST-KAB', 'name' => 'Koperasi Angkutan Bersama', 'type' => 'company', 'payment_term_days' => 30, 'phone' => '0813450109'],
            ['code' => 'CUST-MUM', 'name' => 'Mitra Usaha Mandiri', 'type' => 'company', 'payment_term_days' => 14, 'phone' => '0813450110'],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $customers[$c['code']] = Customer::create($c);
        }

        return $customers;
    }

    /**
     * Seed 18 Unit Alat Berat Realistis.
     */
    protected function seedEquipmentUnits(array $customers): array
    {
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

        $units = [];
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

        return $units;
    }

    /**
     * Seed 14 Jasa dan Tarif Khusus pada 3 Customer.
     */
    protected function seedServices(array $customers): array
    {
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

        $services = [];
        foreach ($servicesData as $s) {
            $services[$s['code']] = Service::create($s);
        }

        // 3 Customer memiliki tarif khusus untuk 3-4 jasa (SEED_SPEC)
        // 1. PT Borneo Tambang Pratama (CUST-BTP)
        CustomerServiceRate::create(['customer_id' => $customers['CUST-BTP']->id, 'service_id' => $services['SRV-002']->id, 'price' => 650000]);
        CustomerServiceRate::create(['customer_id' => $customers['CUST-BTP']->id, 'service_id' => $services['SRV-003']->id, 'price' => 1300000]);
        CustomerServiceRate::create(['customer_id' => $customers['CUST-BTP']->id, 'service_id' => $services['SRV-004']->id, 'price' => 3000000]);
        CustomerServiceRate::create(['customer_id' => $customers['CUST-BTP']->id, 'service_id' => $services['SRV-012']->id, 'price' => 800000]);

        // 2. PT Andalas Logistik Perkasa (CUST-ALP)
        CustomerServiceRate::create(['customer_id' => $customers['CUST-ALP']->id, 'service_id' => $services['SRV-001']->id, 'price' => 200000]);
        CustomerServiceRate::create(['customer_id' => $customers['CUST-ALP']->id, 'service_id' => $services['SRV-002']->id, 'price' => 680000]);
        CustomerServiceRate::create(['customer_id' => $customers['CUST-ALP']->id, 'service_id' => $services['SRV-014']->id, 'price' => 550000]);

        // 3. CV Nusantara Rental Alat (CUST-NRA)
        CustomerServiceRate::create(['customer_id' => $customers['CUST-NRA']->id, 'service_id' => $services['SRV-005']->id, 'price' => 1000000]);
        CustomerServiceRate::create(['customer_id' => $customers['CUST-NRA']->id, 'service_id' => $services['SRV-008']->id, 'price' => 750000]);
        CustomerServiceRate::create(['customer_id' => $customers['CUST-NRA']->id, 'service_id' => $services['SRV-010']->id, 'price' => 1500000]);
        CustomerServiceRate::create(['customer_id' => $customers['CUST-NRA']->id, 'service_id' => $services['SRV-012']->id, 'price' => 750000]);

        return $services;
    }

    /**
     * Seed 5 Supplier Fiktif.
     */
    protected function seedSuppliers(): array
    {
        $suppliersData = [
            ['code' => 'SUP-001', 'name' => 'PT Distributor Filter Indo', 'phone' => '021-555101'],
            ['code' => 'SUP-002', 'name' => 'PT Pelumas Prima Nusantara', 'phone' => '021-555102'],
            ['code' => 'SUP-003', 'name' => 'PT Undercarriage Perkasa', 'phone' => '021-555103'],
            ['code' => 'SUP-004', 'name' => 'PT Cipta Seal & Hidrolik Mandiri', 'phone' => '021-555104'],
            ['code' => 'SUP-005', 'name' => 'PT Mega Heavy Parts Internasional', 'phone' => '021-555105'],
        ];

        $suppliers = [];
        foreach ($suppliersData as $s) {
            $suppliers[] = Supplier::create($s);
        }

        return $suppliers;
    }

    /**
     * Seed 45 Sparepart di 8 Kategori, dengan Pembelian Masuk via StockService (BR-S7).
     * Minimal 8 part memiliki pembelian berulang dengan harga beda.
     */
    protected function seedPartsAndPurchases(array $suppliers, User $admin): array
    {
        $partsSpec = [
            // Filter (8 items)
            ['sku' => 'SP-0001', 'name' => 'Filter Oli Mesin HD', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 85000, 'min' => 5],
            ['sku' => 'SP-0002', 'name' => 'Filter Solar Primer Racor', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 120000, 'min' => 5],
            ['sku' => 'SP-0003', 'name' => 'Filter Solar Sekunder Spin-On', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 95000, 'min' => 5],
            ['sku' => 'SP-0004', 'name' => 'Filter Udara Luar Outer Air', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 220000, 'min' => 3],
            ['sku' => 'SP-0005', 'name' => 'Filter Udara Dalam Inner Safety', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 140000, 'min' => 2],
            ['sku' => 'SP-0006', 'name' => 'Filter Hidrolik Return Line', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 280000, 'min' => 4],
            ['sku' => 'SP-0007', 'name' => 'Filter Hidrolik Suction Strainer', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 310000, 'min' => 3],
            ['sku' => 'SP-0008', 'name' => 'Filter Transmisi Powershift', 'category' => 'Filter', 'unit' => 'Pcs', 'base_cost' => 190000, 'min' => 3],

            // Oli & Pelumas (7 items)
            ['sku' => 'SP-0009', 'name' => 'Oli Mesin Diesel 15W-40 Drum 200L', 'category' => 'Oli & Pelumas', 'unit' => 'Drum', 'base_cost' => 6200000, 'min' => 1],
            ['sku' => 'SP-0010', 'name' => 'Oli Mesin Diesel 15W-40 Pail 20L', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 680000, 'min' => 4],
            ['sku' => 'SP-0011', 'name' => 'Oli Hidrolik ISO VG 68 Pail 20L', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 640000, 'min' => 4],
            ['sku' => 'SP-0012', 'name' => 'Oli Hidrolik ISO VG 46 Drum 200L', 'category' => 'Oli & Pelumas', 'unit' => 'Drum', 'base_cost' => 5800000, 'min' => 1],
            ['sku' => 'SP-0013', 'name' => 'Oli Transmisi SAE 50 Pail 20L', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 720000, 'min' => 3],
            ['sku' => 'SP-0014', 'name' => 'Grease Heavy Duty EP-2 Pail 15Kg', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 550000, 'min' => 3],
            ['sku' => 'SP-0015', 'name' => 'Coolant Radiator Premix 50/50 Pail 20L', 'category' => 'Oli & Pelumas', 'unit' => 'Pail', 'base_cost' => 420000, 'min' => 2],

            // Hidrolik (5 items)
            ['sku' => 'SP-0016', 'name' => 'Selang Hidrolik 2-Wire 3/4 inch 2M', 'category' => 'Hidrolik', 'unit' => 'Pcs', 'base_cost' => 260000, 'min' => 4],
            ['sku' => 'SP-0017', 'name' => 'Selang Hidrolik 4-Spiral 1 inch 3M', 'category' => 'Hidrolik', 'unit' => 'Pcs', 'base_cost' => 580000, 'min' => 3],
            ['sku' => 'SP-0018', 'name' => 'Fitting Flange / Adaptor NPT 3/4', 'category' => 'Hidrolik', 'unit' => 'Pcs', 'base_cost' => 45000, 'min' => 10],
            ['sku' => 'SP-0019', 'name' => 'Quick Coupler Hidrolik 1/2 High Pressure', 'category' => 'Hidrolik', 'unit' => 'Set', 'base_cost' => 320000, 'min' => 3],
            ['sku' => 'SP-0020', 'name' => 'Rod Cylinder Boom Excavator 20-Ton', 'category' => 'Hidrolik', 'unit' => 'Pcs', 'base_cost' => 4200000, 'min' => 1], // Stok 0 nantinya

            // Undercarriage (7 items)
            ['sku' => 'SP-0021', 'name' => 'Track Shoe PC200 600mm Triple Grouser', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 480000, 'min' => 6],
            ['sku' => 'SP-0022', 'name' => 'Track Link Assembly PC200 45-Link', 'category' => 'Undercarriage', 'unit' => 'Set', 'base_cost' => 7500000, 'min' => 1],
            ['sku' => 'SP-0023', 'name' => 'Track Roller Single Flange PC200', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 620000, 'min' => 4],
            ['sku' => 'SP-0024', 'name' => 'Track Roller Double Flange PC200', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 680000, 'min' => 4],
            ['sku' => 'SP-0025', 'name' => 'Carrier Roller / Top Roller PC200', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 520000, 'min' => 3],
            ['sku' => 'SP-0026', 'name' => 'Sprocket Segment Teeth D85', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 880000, 'min' => 3],
            ['sku' => 'SP-0027', 'name' => 'Front Idler Assembly PC200', 'category' => 'Undercarriage', 'unit' => 'Pcs', 'base_cost' => 3800000, 'min' => 1],

            // Engine (7 items)
            ['sku' => 'SP-0028', 'name' => 'Piston Engine Diesel 6D102', 'category' => 'Engine', 'unit' => 'Set', 'base_cost' => 1450000, 'min' => 2],
            ['sku' => 'SP-0029', 'name' => 'Ring Piston Set 6-Cylinder', 'category' => 'Engine', 'unit' => 'Set', 'base_cost' => 850000, 'min' => 2],
            ['sku' => 'SP-0030', 'name' => 'Liner Cylinder / Boring 6D102', 'category' => 'Engine', 'unit' => 'Pcs', 'base_cost' => 620000, 'min' => 4],
            ['sku' => 'SP-0031', 'name' => 'Nozzle Injector Common Rail', 'category' => 'Engine', 'unit' => 'Pcs', 'base_cost' => 1250000, 'min' => 4],
            ['sku' => 'SP-0032', 'name' => 'V-Belt Fan & Alternator Set', 'category' => 'Engine', 'unit' => 'Set', 'base_cost' => 160000, 'min' => 4],
            ['sku' => 'SP-0033', 'name' => 'Water Pump Assembly Engine', 'category' => 'Engine', 'unit' => 'Pcs', 'base_cost' => 950000, 'min' => 2],
            ['sku' => 'SP-0034', 'name' => 'Turbocharger Assembly PC200-8', 'category' => 'Engine', 'unit' => 'Pcs', 'base_cost' => 5500000, 'min' => 1],

            // Kelistrikan (6 items)
            ['sku' => 'SP-0035', 'name' => 'Aki Basah Heavy Duty 12V 100Ah', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 1350000, 'min' => 3],
            ['sku' => 'SP-0036', 'name' => 'Aki Maintenance Free 12V 150Ah', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 2100000, 'min' => 2],
            ['sku' => 'SP-0037', 'name' => 'Alternator 24V 60A HD', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 1850000, 'min' => 2],
            ['sku' => 'SP-0038', 'name' => 'Starter Motor 24V 7.5kW', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 2450000, 'min' => 1],
            ['sku' => 'SP-0039', 'name' => 'Lampu Kerja LED Worklamp 48W 24V', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 175000, 'min' => 6],
            ['sku' => 'SP-0040', 'name' => 'Sensor Tekanan Oli Engine 24V', 'category' => 'Kelistrikan', 'unit' => 'Pcs', 'base_cost' => 350000, 'min' => 3],

            // Ground Engaging Tools / GET (4 items)
            ['sku' => 'SP-0041', 'name' => 'Tooth Bucket Standard PC200', 'category' => 'Ground Engaging Tools', 'unit' => 'Pcs', 'base_cost' => 135000, 'min' => 10],
            ['sku' => 'SP-0042', 'name' => 'Pin & Retainer Lock Tooth Bucket', 'category' => 'Ground Engaging Tools', 'unit' => 'Set', 'base_cost' => 25000, 'min' => 20],
            ['sku' => 'SP-0043', 'name' => 'Side Cutter Bucket Kiri & Kanan', 'category' => 'Ground Engaging Tools', 'unit' => 'Set', 'base_cost' => 780000, 'min' => 3],
            ['sku' => 'SP-0044', 'name' => 'Cutting Edge Blade Bulldozer D85', 'category' => 'Ground Engaging Tools', 'unit' => 'Pcs', 'base_cost' => 1100000, 'min' => 3],

            // Seal & Gasket (1 item)
            ['sku' => 'SP-0045', 'name' => 'Seal Kit Boom Cylinder PC200', 'category' => 'Seal & Gasket', 'unit' => 'Set', 'base_cost' => 450000, 'min' => 4],
        ];

        $parts = [];

        // 1. Buat master part (stok awal 0)
        foreach ($partsSpec as $spec) {
            // Margin acak 15% s.d. 45% (SEED_SPEC Invarian 2)
            $marginPercent = rand(18, 42);
            $sellPrice = (int) round($spec['base_cost'] * (1 + $marginPercent / 100), -3); // Bulatkan ke ribuan terdekat

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

        // 2. Buat Transaksi Pembelian Masuk via StockService (BR-S7)
        // Batch 1: Pembelian awal 11 bulan lalu (Nov 2025)
        $batch1Items = [];
        foreach ($parts as $sku => $part) {
            // Jumlah awal cukup besar agar tidak kehabisan stok selama 110 order servis
            if ($sku === 'SP-0020') {
                $qty = 5;
            } elseif (in_array($part->category, ['Filter', 'Oli & Pelumas', 'Ground Engaging Tools'], true)) {
                $qty = rand(80, 150);
            } else {
                $qty = rand(35, 60);
            }

            $spec = collect($partsSpec)->firstWhere('sku', $sku);
            $cost = $spec['base_cost'];
            $batch1Items[] = [
                'part_id' => $part->id,
                'qty' => $qty,
                'unit_cost' => $cost,
            ];
        }

        $this->createPurchase(
            supplierId: $suppliers[0]->id,
            date: '2025-11-05',
            ref: 'PO-202511-0001',
            items: $batch1Items,
            userId: $admin->id
        );

        // Batch 2: Pembelian kedua 6 bulan lalu (Mei 2026) untuk ≥ 8 part dengan harga beli berbeda
        // (menghasilkan rata-rata tertimbang non-trivial, BR-S7)
        $batch2Items = [];
        for ($i = 1; $i <= 10; $i++) {
            $sku = sprintf('SP-%04d', $i);
            $part = $parts[$sku];
            $spec = collect($partsSpec)->firstWhere('sku', $sku);
            // Variasi harga beli naik 5% - 10%
            $variedCost = (int) round($spec['base_cost'] * 1.08, -3);

            $batch2Items[] = [
                'part_id' => $part->id,
                'qty' => rand(40, 70),
                'unit_cost' => $variedCost,
            ];
        }

        $this->createPurchase(
            supplierId: $suppliers[1]->id,
            date: '2026-05-10',
            ref: 'PO-202605-0002',
            items: $batch2Items,
            userId: $admin->id
        );

        // Perbarui harga jual agar selalu > avg_cost baru dengan margin 15-45%
        foreach ($parts as $part) {
            $part->refresh();
            $marginPercent = rand(20, 40);
            $newSellPrice = (int) round($part->avg_cost * (1 + $marginPercent / 100), -3);
            if ($newSellPrice <= $part->avg_cost) {
                $newSellPrice = (int) ($part->avg_cost * 1.25);
            }
            $part->sell_price = $newSellPrice;
            $part->save();
        }

        return $parts;
    }

    /**
     * Helper membuat transaksi pembelian barang masuk lengkap dengan item dan mutasi stok.
     */
    protected function createPurchase(int $supplierId, string $date, string $ref, array $items, int $userId): Purchase
    {
        $total = 0;
        foreach ($items as $item) {
            $total += ((int) $item['qty'] * (int) $item['unit_cost']);
        }

        return DB::transaction(function () use ($supplierId, $date, $ref, $items, $total, $userId) {
            $purchase = Purchase::create([
                'supplier_id' => $supplierId,
                'purchase_date' => $date,
                'reference' => $ref,
                'total' => $total,
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                \App\Models\PurchaseItem::create([
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
                    userId: $userId
                );
            }

            return $purchase;
        });
    }

    /**
     * Seed ≥ 2 Penyesuaian Stok (BR-S6).
     */
    protected function seedStockAdjustments(array $parts, User $admin): void
    {
        // 1. Selisih fisik baut/fitting: +5 unit karena salah hitung fisik (ADJUSTMENT_IN)
        $this->stockService->recordAdjustment(
            partId: $parts['SP-0018']->id,
            type: 'ADJUSTMENT_IN',
            qty: 5,
            reason: 'Hasil Stock Opname triwulan I - kelebihan fisik fitting',
            userId: $admin->id
        );

        // 2. Selisih fisik V-Belt: -1 unit rusak terkelupas di rak (ADJUSTMENT_OUT)
        $this->stockService->recordAdjustment(
            partId: $parts['SP-0032']->id,
            type: 'ADJUSTMENT_OUT',
            qty: 1,
            reason: 'Hasil Stock Opname triwulan II - 1 pcs v-belt robek di gudang',
            userId: $admin->id
        );
    }

    /**
     * Seed Pengeluaran Operasional (±40 transaksi) over 12 months.
     */
    protected function seedExpenses(User $admin): void
    {
        $categories = [
            'Listrik & Air' => [450000, 750000],
            'Sewa Tempat' => [3500000, 3500000],
            'Gaji & Upah' => [4500000, 6000000],
            'Konsumsi' => [150000, 300000],
            'Transportasi & Logistik' => [250000, 600000],
            'Perlengkapan Bengkel' => [300000, 850000],
        ];

        // 12 bulan (Nov 2025 s.d. Okt 2026), ~3-4 pengeluaran per bulan
        for ($monthOffset = 11; $monthOffset >= 0; $monthOffset--) {
            $monthDate = Carbon::parse('2026-10-15')->subMonths($monthOffset);

            // Sewa (tiap bulan)
            Expense::create([
                'expense_date' => $monthDate->copy()->startOfMonth()->addDays(2)->toDateString(),
                'category' => 'Sewa Tempat',
                'description' => 'Sewa workshop bulan ' . $monthDate->translatedFormat('F Y'),
                'amount' => 3500000,
                'created_by' => $admin->id,
            ]);

            // Listrik & Air
            Expense::create([
                'expense_date' => $monthDate->copy()->startOfMonth()->addDays(10)->toDateString(),
                'category' => 'Listrik & Air',
                'description' => 'Tagihan listrik & air workshop ' . $monthDate->translatedFormat('F Y'),
                'amount' => rand(480000, 720000),
                'created_by' => $admin->id,
            ]);

            // Konsumsi lembur
            Expense::create([
                'expense_date' => $monthDate->copy()->startOfMonth()->addDays(18)->toDateString(),
                'category' => 'Konsumsi',
                'description' => 'Konsumsi & kopi mekanik pengerjaan lembur',
                'amount' => rand(180000, 320000),
                'created_by' => $admin->id,
            ]);

            // Transportasi bila bulan ganjil
            if ($monthOffset % 2 === 1) {
                Expense::create([
                    'expense_date' => $monthDate->copy()->startOfMonth()->addDays(24)->toDateString(),
                    'category' => 'Transportasi & Logistik',
                    'description' => 'Biaya solar armada servis keliling & antar part',
                    'amount' => rand(350000, 550000),
                    'created_by' => $admin->id,
                ]);
            }
        }
    }

    /**
     * Seed Transaksi Order Servis (±110) dan Penjualan Langsung (±60)
     * Menggunakan alur service resmi (WorkOrderService, InvoiceService, PaymentService).
     */
    protected function seedTransactions(
        array $customers,
        array $units,
        array $services,
        array $parts,
        User $admin,
        User $cashier,
        array $mechanics
    ): void {
        $customersList = array_values($customers);
        $unitsList = array_values($units);
        $servicesList = array_values($services);
        $partsList = array_values($parts);

        // Koleksi invoice yang akan dibuat pembayarannya
        $createdInvoices = [];

        // 1. Seed Order Servis (Total 110 order servis)
        // Distribusi status:
        // - 2 open
        // - 2 in_progress
        // - 3 completed (belum invoiced)
        // - 2 cancelled (stok dikembalikan via SERVICE_RETURN)
        // - 101 invoiced (diterbitkan invoice)
        for ($i = 1; $i <= 110; $i++) {
            // Rentang waktu 12 bulan (Nov 2025 s.d. Okt 2026), tren naik
            $daysAgo = (int) round(360 * pow((110 - $i) / 110, 1.2));
            $orderDate = Carbon::parse('2026-10-20')->subDays($daysAgo)->setTime(rand(8, 16), rand(0, 59));

            // Pilih unit dan customer pemiliknya
            $unit = $unitsList[$i % count($unitsList)];
            $customer = $unit->customer;
            $mechanic = $mechanics[$i % count($mechanics)];

            $wo = $this->workOrderService->createWorkOrder([
                'customer_id' => $customer->id,
                'equipment_unit_id' => $unit->id,
                'complaint' => 'Servis berkala dan perbaikan unit nomor kerja ' . $i,
                'diagnosis' => 'Pemeriksaan rutin komponen mesin dan penggantian pelumas',
                'mechanic_id' => $mechanic->id,
                'opened_at' => $orderDate,
            ], $admin->id);

            // Tambahkan 1 s.d. 2 item sparepart
            $partA = $partsList[($i * 3) % count($partsList)];
            $partA->refresh();
            if ($partA->qty_on_hand >= 5 && $partA->sku !== 'SP-0020') {
                $this->workOrderService->addPartItem($wo, $partA->id, rand(1, 2), null, $admin->id);
            } else {
                // Gunakan filter oli yang stoknya banyak
                $fallback = $parts['SP-0001'];
                $fallback->refresh();
                $this->workOrderService->addPartItem($wo, $fallback->id, 1, null, $admin->id);
            }

            // Tambahkan 1 item jasa
            $service = $servicesList[$i % count($servicesList)];
            $this->workOrderService->addServiceItem($wo, $service->id, 1, $admin->id);

            // Tentukan status target
            if ($i === 109 || $i === 110) {
                // 2 status: open
                // Biarkan status open
                continue;
            } elseif ($i === 107 || $i === 108) {
                // 2 status: in_progress
                $this->workOrderService->transitionStatus($wo, 'in_progress', $mechanic->id);
                continue;
            } elseif ($i === 104 || $i === 105 || $i === 106) {
                // 3 status: completed (belum invoiced)
                $this->workOrderService->transitionStatus($wo, 'in_progress', $mechanic->id);
                $this->workOrderService->transitionStatus($wo, 'completed', $mechanic->id);
                continue;
            } elseif ($i === 102 || $i === 103) {
                // 2 status: cancelled (stok dikembalikan)
                $this->workOrderService->transitionStatus($wo, 'cancelled', $admin->id);
                continue;
            } else {
                // 101 status: invoiced
                $this->workOrderService->transitionStatus($wo, 'in_progress', $mechanic->id);
                $this->workOrderService->transitionStatus($wo, 'completed', $mechanic->id);

                // Tanggal invoice >= tanggal order
                $issueDate = $orderDate->copy()->addDays(rand(0, 2))->toDateString();

                // Berikan diskon pada minimal 3 invoice
                $discount = ($i <= 3) ? 50000 : 0;

                $invoice = $this->invoiceService->createFromWorkOrder($wo, [
                    'issue_date' => $issueDate,
                    'discount_amount' => $discount,
                ], $cashier->id);

                $createdInvoices[] = $invoice;
            }
        }

        // 2. Seed Penjualan Langsung (60 counter sales)
        for ($j = 1; $j <= 60; $j++) {
            $daysAgo = (int) round(355 * pow((60 - $j) / 60, 1.1));
            $saleDate = Carbon::parse('2026-10-20')->subDays($daysAgo)->toDateString();

            // Pelanggan Umum atau Customer terpilih
            $customer = ($j % 3 === 0) ? $customers['CUST-UMUM'] : $customersList[$j % count($customersList)];

            // Pilih 1 s.d. 2 sparepart yang ada stoknya
            $part = $partsList[($j * 5) % count($partsList)];
            $part->refresh();
            if ($part->qty_on_hand < 5 || $part->sku === 'SP-0020') {
                $part = $parts['SP-0002']; // Filter solar banyak stok
                $part->refresh();
            }

            // Diskon pada 2 invoice penjualan langsung
            $discount = ($j <= 2) ? 25000 : 0;

            $invoice = $this->invoiceService->createDirectSale([
                'customer_id' => $customer->id,
                'issue_date' => $saleDate,
                'discount_amount' => $discount,
                'items' => [
                    [
                        'part_id' => $part->id,
                        'qty' => rand(1, 2),
                    ]
                ],
            ], $cashier->id);

            $createdInvoices[] = $invoice;
        }

        // 3. Khusus SP-0020 (Rod Cylinder): Lakukan 1 penjualan langsung terakhir agar stoknya menjadi 0 persis (SEED_SPEC)
        $rodPart = $parts['SP-0020'];
        $rodPart->refresh();
        if ($rodPart->qty_on_hand > 0) {
            $invZero = $this->invoiceService->createDirectSale([
                'customer_id' => $customers['CUST-BTP']->id,
                'issue_date' => '2026-10-18',
                'discount_amount' => 0,
                'items' => [
                    ['part_id' => $rodPart->id, 'qty' => $rodPart->qty_on_hand]
                ],
            ], $cashier->id);
            $createdInvoices[] = $invZero;
        }

        // 4. Pastikan minimal 4 part lainnya dengan stok <= min_stock (SP-0022, SP-0027, SP-0034, SP-0038)
        $lowStockSkus = ['SP-0022', 'SP-0027', 'SP-0034', 'SP-0038'];
        foreach ($lowStockSkus as $lSku) {
            $lPart = $parts[$lSku];
            $lPart->refresh();
            if ($lPart->qty_on_hand > $lPart->min_stock) {
                $diff = $lPart->qty_on_hand - $lPart->min_stock;
                $invLow = $this->invoiceService->createDirectSale([
                    'customer_id' => $customers['CUST-ALP']->id,
                    'issue_date' => '2026-10-19',
                    'discount_amount' => 0,
                    'items' => [
                        ['part_id' => $lPart->id, 'qty' => $diff]
                    ],
                ], $cashier->id);
                $createdInvoices[] = $invLow;
            }
        }

        // 4. Buat 3 Invoice VOID (SEED_SPEC: persis 3 void)
        // Ambil 3 invoice penjualan langsung untuk divoid (menguji SALE_RETURN dan menjaga 3 WO completed)
        $counterSaleInvoices = array_values(array_filter($createdInvoices, fn ($inv) => $inv->source_type === 'counter_sale'));
        $voidTargets = array_slice($counterSaleInvoices, 0, 3);
        $voidIds = array_map(fn ($inv) => $inv->id, $voidTargets);
        $activeInvoices = array_values(array_filter($createdInvoices, fn ($inv) => !in_array($inv->id, $voidIds, true)));

        foreach ($voidTargets as $idx => $invToVoid) {
            $this->invoiceService->voidInvoice(
                invoice: $invToVoid,
                reason: 'Koreksi faktur ganda / kesalahan unit alat demo nomor ' . ($idx + 1),
                userId: $admin->id
            );
        }

        // 5. Seed Pembayaran pada Invoice Aktif
        // SEED_SPEC Distribusi:
        // ±70% lunas, ±15% dibayar sebagian, ±10% belum dibayar
        // 1 Customer dengan tunggakan besar >90 hari (PT Mahakam Mineral Mandiri / CUST-MMM)
        // 2 Customer dengan tunggakan ringan 1-30, 31-60 hari (CUST-JKS, CUST-HDK)
        // ≥ 5 invoice memiliki pembayaran cicilan (2-3 kali bayar)
        $totalActive = count($activeInvoices);
        $installmentCount = 0;
        $maxDate = Carbon::parse('2026-10-20');

        foreach ($activeInvoices as $k => $inv) {
            $inv->refresh();
            $customer = $inv->customer;
            $issueDate = $inv->issue_date;

            // Aturan khusus tunggakan:
            // Jika customer adalah PT Mahakam Mineral Mandiri (CUST-MMM) dan terbit > 90 hari lalu,
            // JANGAN dibayar agar menghasilkan piutang macet > 90 hari (SEED_SPEC)
            if ($customer->code === 'CUST-MMM' && $issueDate->diffInDays($maxDate) > 90) {
                // Jangan bayar atau bayar sebagian kecil saja
                if ($k % 2 === 0) {
                    $partial = (int) round($inv->total * 0.2, -3);
                    if ($partial > 0 && $partial < $inv->total) {
                        $pDate = $issueDate->copy()->addDays(5)->min($maxDate)->toDateString();
                        $this->paymentService->recordPayment($inv, [
                            'amount' => $partial,
                            'paid_at' => $pDate,
                            'method' => 'transfer',
                            'reference' => 'TRF-MMM-' . $k,
                        ], $cashier->id);
                    }
                }
                continue; // Lanjut ke invoice berikutnya
            }

            // Jika customer CUST-JKS atau CUST-HDK pada invoice 30-50 hari lalu, biarkan belum lunas
            if (in_array($customer->code, ['CUST-JKS', 'CUST-HDK'], true) && $k % 3 === 0) {
                $partial = (int) round($inv->total * 0.4, -3);
                if ($partial > 0 && $partial < $inv->total) {
                    $pDate = $issueDate->copy()->addDays(3)->min($maxDate)->toDateString();
                    $this->paymentService->recordPayment($inv, [
                        'amount' => $partial,
                        'paid_at' => $pDate,
                        'method' => 'cash',
                    ], $cashier->id);
                }
                continue;
            }

            // Distribusi umum:
            // Modulo 10:
            // 0 -> Belum dibayar (issued) (~10%)
            // 1, 2 -> Dibayar sebagian (partially_paid) (~20%)
            // 3 s.d. 9 -> Lunas (paid) (~70%)
            $bucketMod = $k % 10;

            if ($bucketMod === 0) {
                // Belum dibayar
                continue;
            } elseif ($bucketMod === 1 || $bucketMod === 2) {
                // Dibayar sebagian (~15-20%)
                $partialAmount = (int) round($inv->total * rand(30, 60) / 100, -3);
                if ($partialAmount > 0 && $partialAmount < $inv->total) {
                    $pDate = $issueDate->copy()->addDays(rand(1, 10))->min($maxDate)->toDateString();
                    $this->paymentService->recordPayment($inv, [
                        'amount' => $partialAmount,
                        'paid_at' => $pDate,
                        'method' => ($k % 2 === 0) ? 'transfer' : 'cash',
                        'reference' => 'PAY-PART-' . $k,
                    ], $cashier->id);
                }
            } else {
                // Lunas (paid) (~70%)
                // Jika masih butuh cicilan (≥ 5 invoice cicilan 2-3 kali)
                if ($installmentCount < 6) {
                    $pay1 = (int) round($inv->total * 0.4, -3);
                    $pay2 = (int) round($inv->total * 0.3, -3);
                    $pay3 = $inv->total - $pay1 - $pay2;

                    $pDate1 = $issueDate->copy()->addDays(rand(1, 3))->min($maxDate)->toDateString();
                    $pDate2 = Carbon::parse($pDate1)->addDays(rand(1, 3))->min($maxDate)->toDateString();
                    $pDate3 = Carbon::parse($pDate2)->addDays(rand(1, 3))->min($maxDate)->toDateString();

                    $this->paymentService->recordPayment($inv, ['amount' => $pay1, 'paid_at' => $pDate1, 'method' => 'transfer'], $cashier->id);
                    $this->paymentService->recordPayment($inv, ['amount' => $pay2, 'paid_at' => $pDate2, 'method' => 'transfer'], $cashier->id);
                    $this->paymentService->recordPayment($inv, ['amount' => $pay3, 'paid_at' => $pDate3, 'method' => 'cash'], $cashier->id);

                    $installmentCount++;
                } else {
                    // Bayar sekaligus lunas
                    $payDate = $issueDate->copy()->addDays(rand(0, 10))->min($maxDate)->toDateString();
                    $this->paymentService->recordPayment($inv, [
                        'amount' => $inv->total,
                        'paid_at' => $payDate,
                        'method' => ($k % 2 === 0) ? 'transfer' : 'cash',
                        'reference' => 'PAY-FULL-' . $k,
                    ], $cashier->id);
                }
            }
        }
    }

    /**
     * Verifikasi Semua Invarian Bisnis (Wajib SEED_SPEC).
     * Jika ada yang tidak terpenuhi, lempar Exception (seeding gagal).
     */
    public function assertAllInvariants(): void
    {
        // Invarian 1: Tidak ada stok minus; qty_on_hand = Σ movements untuk setiap part (BR-S1)
        $allParts = Part::all();
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

        // Invarian 2: harga_jual > avg_cost untuk semua part (margin acak 15% - 45%)
        foreach ($allParts as $part) {
            if ($part->is_active && $part->sell_price <= $part->avg_cost) {
                throw new \RuntimeException(
                    "Pelanggaran Invarian 2: Harga jual ({$part->sell_price}) <= avg_cost ({$part->avg_cost}) pada part {$part->sku}"
                );
            }
        }

        // Invarian 3 & 4: Setiap invoice valid dan tidak ada pembayaran invoice void
        $allInvoices = Invoice::with(['items', 'validPayments'])->get();
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

            // Invarian 4: Tidak ada pembayaran pada invoice void
            if ($inv->status === 'void' && $inv->validPayments()->count() > 0) {
                throw new \RuntimeException(
                    "Pelanggaran Invarian 4: Invoice void {$inv->invoice_number} memiliki pembayaran valid!"
                );
            }
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
        foreach (Payment::with('invoice.workOrder')->where('status', 'valid')->get() as $p) {
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
        }
    }

    /**
     * Cetak Ringkasan Angka Akhir (SEED_SPEC).
     */
    protected function printSummary(): void
    {
        $this->newLine();
        $this->line("==========================================================================");
        $this->line("                  RINGKASAN DATA SEEDING DEMO (SEED_SPEC)                 ");
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

        // 3. Status Invoice
        $this->table(['Status Invoice', 'Jumlah'], [
            ['Lunas (paid)', Invoice::where('status', 'paid')->count()],
            ['Dibayar Sebagian (partially_paid)', Invoice::where('status', 'partially_paid')->count()],
            ['Belum Dibayar (issued)', Invoice::where('status', 'issued')->count()],
            ['Dibatalkan (void)', Invoice::where('status', 'void')->count()],
            ['TOTAL SEMUA INVOICE', Invoice::count()],
        ]);

        // 4. Distribusi Aging Piutang
        $this->table(['Distribusi Umur Piutang (BR-R7)', 'Nominal'], [
            ['Belum Jatuh Tempo (<=0)', 'Rp ' . number_format($rec['aging']['not_due'], 0, ',', '.')],
            ['1 - 30 Hari', 'Rp ' . number_format($rec['aging']['days_1_30'], 0, ',', '.')],
            ['31 - 60 Hari', 'Rp ' . number_format($rec['aging']['days_31_60'], 0, ',', '.')],
            ['61 - 90 Hari', 'Rp ' . number_format($rec['aging']['days_61_90'], 0, ',', '.')],
            ['> 90 Hari (Tunggakan Macet)', 'Rp ' . number_format($rec['aging']['days_over_90'], 0, ',', '.')],
        ]);

        // 5. Peringatan Stok
        $lowStockCount = Part::where('is_active', true)->whereColumn('qty_on_hand', '<=', 'min_stock')->count();
        $zeroStockCount = Part::where('is_active', true)->where('qty_on_hand', 0)->count();

        $this->line("Peringatan Stok Menipis: {$lowStockCount} part <= batas minimum (1 part dengan stok 0 persis)");
        $this->line("==========================================================================");
        $this->newLine();
    }
}
