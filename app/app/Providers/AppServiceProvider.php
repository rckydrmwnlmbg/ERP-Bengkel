<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hak Akses per Peran sesuai docs/PRD.md:

        // 1. Master data (customer, part, jasa, supplier)
        // Admin: CRUD; Kasir: Lihat, tambah customer; Mekanik: Lihat
        Gate::define('master-data.manage', fn (User $user) => $user->isAdmin());
        Gate::define('master-data.view', fn (User $user) => true);
        Gate::define('customer.create', fn (User $user) => in_array($user->role, ['admin', 'cashier'], true));

        // 2. Harga beli / HPP / avg_cost / laba
        // Admin: Lihat; Kasir: Tidak; Mekanik: Tidak
        Gate::define('view-cost-price', fn (User $user) => $user->isAdmin());

        // 3. Order servis
        // Admin: Semua; Kasir: Lihat; Mekanik: Buat, isi item, ubah status
        Gate::define('service-orders.view', fn (User $user) => true);
        Gate::define('service-orders.manage', fn (User $user) => in_array($user->role, ['admin', 'mechanic'], true));

        // 4. Penjualan sparepart langsung (counter sale)
        // Admin: Ya; Kasir: Ya; Mekanik: Tidak
        Gate::define('direct-sales.manage', fn (User $user) => in_array($user->role, ['admin', 'cashier'], true));

        // 5. Invoice dan pembayaran
        // Admin: Ya, termasuk void; Kasir: Buat, bayar; Mekanik: Tidak
        Gate::define('invoices.manage', fn (User $user) => in_array($user->role, ['admin', 'cashier'], true));
        Gate::define('payments.manage', fn (User $user) => in_array($user->role, ['admin', 'cashier'], true));
        Gate::define('void-financial', fn (User $user) => $user->isAdmin());

        // 6. Barang masuk (purchases), penyesuaian stok
        // Admin: Ya; Kasir: Tidak; Mekanik: Tidak
        Gate::define('inventory.manage', fn (User $user) => $user->isAdmin());

        // 7. Pengeluaran
        // Admin: Ya; Kasir: Tidak; Mekanik: Tidak
        Gate::define('expenses.manage', fn (User $user) => $user->isAdmin());

        // 8. Laporan, audit log, pengaturan
        // Admin: Ya; Kasir: Piutang saja; Mekanik: Tidak
        Gate::define('reports.view', fn (User $user) => $user->isAdmin());
        Gate::define('reports.receivables.view', fn (User $user) => in_array($user->role, ['admin', 'cashier'], true));
        Gate::define('audit-logs.view', fn (User $user) => $user->isAdmin());
        Gate::define('settings.manage', fn (User $user) => $user->isAdmin());
    }
}
