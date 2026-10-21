<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/db-test', function () {
    try {
        $connection = \Illuminate\Support\Facades\DB::connection();
        $pdo = $connection->getPdo();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();
        $userCount = \Illuminate\Support\Facades\DB::table('users')->count();
        $users = \Illuminate\Support\Facades\DB::table('users')->select('id', 'name', 'email', 'role')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Database terhubung dengan sukses!',
            'driver' => $driver,
            'database' => $database,
            'user_count' => $userCount,
            'users' => $users,
            'env_db_connection' => env('DB_CONNECTION'),
            'has_postgres_url' => !empty(env('POSTGRES_URL')),
            'has_database_url' => !empty(env('DATABASE_URL')),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'driver_in_config' => config('database.default'),
            'has_postgres_url' => !empty(env('POSTGRES_URL')),
            'has_database_url' => !empty(env('DATABASE_URL')),
        ], 500);
    }
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Fase 2: Master Data
    Route::resource('customers', \App\Http\Controllers\CustomerController::class);
    Route::post('customers/{customer}/equipment-units', [\App\Http\Controllers\EquipmentUnitController::class, 'store'])->name('customers.equipment-units.store');
    Route::put('equipment-units/{equipmentUnit}', [\App\Http\Controllers\EquipmentUnitController::class, 'update'])->name('equipment-units.update');
    Route::delete('equipment-units/{equipmentUnit}', [\App\Http\Controllers\EquipmentUnitController::class, 'destroy'])->name('equipment-units.destroy');

    Route::post('customers/{customer}/rates', [\App\Http\Controllers\CustomerServiceRateController::class, 'store'])->name('customers.rates.store');
    Route::delete('customers/{customer}/rates/{rate}', [\App\Http\Controllers\CustomerServiceRateController::class, 'destroy'])->name('customers.rates.destroy');

    Route::get('parts/{part}/stock-card', [\App\Http\Controllers\PartStockCardController::class, 'show'])->name('parts.stock-card');
    Route::resource('parts', \App\Http\Controllers\PartController::class);
    Route::resource('services', \App\Http\Controllers\ServiceController::class);
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);

    // Fase 3: Barang Masuk (Purchases) & Penyesuaian Stok
    Route::resource('purchases', \App\Http\Controllers\PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('stock-adjustments', \App\Http\Controllers\StockAdjustmentController::class)->only(['index', 'create', 'store']);

    // Fase 4: Order Servis (Work Orders) & Item Servis
    Route::resource('work-orders', \App\Http\Controllers\WorkOrderController::class);
    Route::post('work-orders/{workOrder}/status', [\App\Http\Controllers\WorkOrderController::class, 'transitionStatus'])->name('work-orders.status');
    Route::post('work-orders/{workOrder}/parts', [\App\Http\Controllers\WorkOrderItemController::class, 'storePart'])->name('work-orders.parts.store');
    Route::post('work-orders/{workOrder}/services', [\App\Http\Controllers\WorkOrderItemController::class, 'storeService'])->name('work-orders.services.store');
    Route::delete('work-orders/{workOrder}/items/{item}', [\App\Http\Controllers\WorkOrderItemController::class, 'destroy'])->name('work-orders.items.destroy');

    // Fase 5: Invoice, Penjualan Langsung, Pembayaran, dan Piutang
    Route::get('invoices/from-work-order/{workOrder}', [\App\Http\Controllers\InvoiceController::class, 'createFromWorkOrder'])->name('invoices.create-from-wo');
    Route::post('invoices/from-work-order/{workOrder}', [\App\Http\Controllers\InvoiceController::class, 'storeFromWorkOrder'])->name('invoices.store-from-wo');
    Route::get('direct-sales/create', [\App\Http\Controllers\InvoiceController::class, 'createDirectSale'])->name('direct-sales.create');
    Route::post('direct-sales', [\App\Http\Controllers\InvoiceController::class, 'storeDirectSale'])->name('direct-sales.store');

    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class)->only(['index', 'show']);
    Route::get('invoices/{invoice}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
    Route::post('invoices/{invoice}/void', [\App\Http\Controllers\InvoiceController::class, 'void'])->name('invoices.void');

    Route::post('invoices/{invoice}/payments', [\App\Http\Controllers\PaymentController::class, 'store'])->name('invoices.payments.store');
    Route::post('payments/{payment}/void', [\App\Http\Controllers\PaymentController::class, 'void'])->name('payments.void');

    Route::get('receivables', [\App\Http\Controllers\ReceivableController::class, 'index'])->name('receivables.index');

    // Fase 6: Pengeluaran Operasional (Expenses)
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class);

    // Fase 6: Laporan (Reports) & Ekspor
    Route::get('reports/revenue', [\App\Http\Controllers\ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('reports/profit-loss', [\App\Http\Controllers\ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('reports/cash-flow', [\App\Http\Controllers\ReportController::class, 'cashFlow'])->name('reports.cash-flow');
    Route::get('reports/receivables', [\App\Http\Controllers\ReportController::class, 'receivables'])->name('reports.receivables');
    Route::get('reports/export/{type}', [\App\Http\Controllers\ReportController::class, 'exportExcel'])->name('reports.export-excel');
    Route::get('reports/print/{type}', [\App\Http\Controllers\ReportController::class, 'print'])->name('reports.print');

    // Fase 8: Audit Log & Pengaturan Sistem
    Route::get('audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

    // Route khusus Admin (PRD: Laporan penuh, HPP, Penyesuaian stok, Pengaturan, Audit Log)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', fn () => response()->json(['status' => 'ok', 'message' => 'Admin Dashboard Access Granted']))->name('dashboard');
        Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings');
        Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs');
    });

    // Route untuk Admin dan Kasir (PRD: Penjualan langsung, Invoice, Pembayaran)
    Route::middleware('role:admin,cashier')->prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/direct-sales', fn () => response()->json(['status' => 'ok', 'message' => 'Direct Sales Access Granted']))->name('direct-sales');
        Route::get('/invoices', fn () => response()->json(['status' => 'ok', 'message' => 'Invoices Access Granted']))->name('invoices');
    });

    // Route untuk Admin dan Mekanik (PRD: Order Servis)
    Route::middleware('role:admin,mechanic')->prefix('mechanic')->name('mechanic.')->group(function () {
        Route::get('/service-orders', fn () => response()->json(['status' => 'ok', 'message' => 'Service Orders Access Granted']))->name('service-orders');
    });
});

require __DIR__.'/auth.php';
