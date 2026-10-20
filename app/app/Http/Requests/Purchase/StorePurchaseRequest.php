<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_id' => ['required', 'exists:parts,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier / pemasok wajib dipilih.',
            'supplier_id.exists' => 'Supplier tidak valid.',
            'purchase_date.required' => 'Tanggal pembelian / barang masuk wajib diisi.',
            'items.required' => 'Minimal harus ada 1 item sparepart yang dibeli.',
            'items.min' => 'Minimal harus ada 1 item sparepart yang dibeli.',
            'items.*.part_id.required' => 'Sparepart wajib dipilih untuk setiap baris.',
            'items.*.part_id.exists' => 'Sparepart yang dipilih tidak ditemukan.',
            'items.*.qty.required' => 'Kuantitas barang masuk wajib diisi.',
            'items.*.qty.integer' => 'Kuantitas harus berupa angka bulat positif.',
            'items.*.qty.min' => 'Kuantitas minimal 1 unit.',
            'items.*.unit_cost.required' => 'Harga beli satuan wajib diisi.',
            'items.*.unit_cost.integer' => 'Harga beli harus berupa integer rupiah.',
            'items.*.unit_cost.min' => 'Harga beli tidak boleh minus.',
        ];
    }
}
