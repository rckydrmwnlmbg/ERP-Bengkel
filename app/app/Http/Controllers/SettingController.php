<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Tampilkan halaman pengaturan umum sistem (Admin only).
     */
    public function index(): View
    {
        Gate::authorize('settings.manage');

        $shopName = Setting::get('shop_name', 'Bengkel Alat Berat Mandiri');
        $taxRate = Setting::getTaxRate();

        return view('settings.index', compact('shopName', 'taxRate'));
    }

    /**
     * Perbarui pengaturan umum sistem.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('settings.manage');

        $validated = $request->validate([
            'shop_name' => ['required', 'string', 'max:100'],
            'tax_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'shop_name.required' => 'Nama bengkel wajib diisi.',
            'tax_rate_percent.required' => 'Tarif pajak wajib diisi.',
            'tax_rate_percent.numeric' => 'Tarif pajak harus berupa angka.',
            'tax_rate_percent.min' => 'Tarif pajak minimal 0%.',
            'tax_rate_percent.max' => 'Tarif pajak maksimal 100%.',
        ]);

        $oldShopName = Setting::get('shop_name', 'Bengkel Alat Berat Mandiri');
        $oldTaxRate = Setting::getTaxRate();

        Setting::set('shop_name', $validated['shop_name']);
        Setting::set('tax_rate_percent', $validated['tax_rate_percent']);

        // Catat ke Audit Log jika ada perubahan
        if ($oldShopName !== $validated['shop_name'] || (float)$oldTaxRate !== (float)$validated['tax_rate_percent']) {
            $this->auditService->log(
                action: 'SETTINGS_UPDATE',
                entityType: 'Setting',
                entityId: 1,
                oldValues: [
                    'shop_name' => $oldShopName,
                    'tax_rate_percent' => $oldTaxRate,
                ],
                newValues: [
                    'shop_name' => $validated['shop_name'],
                    'tax_rate_percent' => $validated['tax_rate_percent'],
                ],
                userId: Auth::id()
            );
        }

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
