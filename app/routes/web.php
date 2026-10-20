<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

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

    // Route khusus Admin (PRD: Laporan penuh, HPP, Penyesuaian stok, Pengaturan, Audit Log)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', fn () => response()->json(['status' => 'ok', 'message' => 'Admin Dashboard Access Granted']))->name('dashboard');
        Route::get('/settings', fn () => response()->json(['status' => 'ok', 'message' => 'Settings Access Granted']))->name('settings');
        Route::get('/audit-logs', fn () => response()->json(['status' => 'ok', 'message' => 'Audit Logs Access Granted']))->name('audit-logs');
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
