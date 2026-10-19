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
