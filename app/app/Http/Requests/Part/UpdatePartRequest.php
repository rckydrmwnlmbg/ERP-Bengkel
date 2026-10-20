<?php

namespace App\Http\Requests\Part;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master-data.manage') ?? false;
    }

    public function rules(): array
    {
        $part = $this->route('part');

        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('parts', 'sku')->ignore($part)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'sell_price' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'Nomor SKU / Part Number wajib diisi.',
            'sku.unique' => 'SKU sudah terdaftar.',
            'name.required' => 'Nama sparepart wajib diisi.',
            'category.required' => 'Kategori sparepart wajib diisi.',
            'unit.required' => 'Satuan sparepart wajib diisi.',
            'sell_price.required' => 'Harga jual wajib diisi.',
            'sell_price.integer' => 'Harga jual harus berupa bilangan bulat rupiah.',
            'sell_price.min' => 'Harga jual minimal Rp0.',
            'min_stock.required' => 'Batas stok minimum wajib diisi.',
            'min_stock.integer' => 'Stok minimum harus berupa angka.',
            'min_stock.min' => 'Stok minimum tidak boleh minus.',
        ];
    }
}
