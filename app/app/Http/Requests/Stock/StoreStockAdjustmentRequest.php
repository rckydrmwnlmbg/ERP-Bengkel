<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'part_id' => ['required', 'exists:parts,id'],
            'type' => ['required', 'in:ADJUSTMENT_IN,ADJUSTMENT_OUT'],
            'qty' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'part_id.required' => 'Sparepart wajib dipilih.',
            'part_id.exists' => 'Sparepart tidak valid.',
            'type.required' => 'Jenis penyesuaian wajib dipilih (Tambah atau Kurangi).',
            'type.in' => 'Jenis penyesuaian harus ADJUSTMENT_IN atau ADJUSTMENT_OUT.',
            'qty.required' => 'Kuantitas penyesuaian wajib diisi.',
            'qty.integer' => 'Kuantitas harus berupa angka bulat positif.',
            'qty.min' => 'Kuantitas minimal 1 unit.',
            'reason.required' => 'Alasan penyesuaian wajib diisi (BR-S6: opname/koreksi selisih).',
            'reason.min' => 'Alasan penyesuaian minimal 3 karakter.',
        ];
    }
}
