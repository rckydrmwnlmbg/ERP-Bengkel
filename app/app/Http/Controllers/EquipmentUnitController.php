<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreEquipmentUnitRequest;
use App\Http\Requests\Customer\UpdateEquipmentUnitRequest;
use App\Models\Customer;
use App\Models\EquipmentUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EquipmentUnitController extends Controller
{
    public function store(StoreEquipmentUnitRequest $request): RedirectResponse
    {
        $unit = EquipmentUnit::create($request->validated());

        return redirect()->route('customers.show', $unit->customer_id)
            ->with('success', "Unit alat berat {$unit->brand} {$unit->model} berhasil ditambahkan.");
    }

    public function update(UpdateEquipmentUnitRequest $request, EquipmentUnit $equipmentUnit): RedirectResponse
    {
        $equipmentUnit->update($request->validated());

        return redirect()->route('customers.show', $equipmentUnit->customer_id)
            ->with('success', "Unit {$equipmentUnit->brand} {$equipmentUnit->model} berhasil diperbarui.");
    }

    public function destroy(EquipmentUnit $equipmentUnit): RedirectResponse
    {
        Gate::authorize('master-data.manage');

        $customerId = $equipmentUnit->customer_id;
        $equipmentUnit->delete();

        return redirect()->route('customers.show', $customerId)
            ->with('success', 'Unit alat berat berhasil dihapus.');
    }
}
