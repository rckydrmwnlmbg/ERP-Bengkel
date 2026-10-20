<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customer.create') || $this->user()?->can('master-data.manage');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'unit_code' => ['nullable', 'string', 'max:50'],
            'hour_meter' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Pelanggan pemilik unit wajib dipilih.',
            'customer_id.exists' => 'Pelanggan tidak valid.',
            'brand.required' => 'Merek alat berat wajib diisi (contoh: Komatsu, Caterpillar, Hitachi).',
            'model.required' => 'Model unit wajib diisi (contoh: PC200-8, D85ESS-2).',
            'hour_meter.integer' => 'Hour meter harus berupa angka bulat positif.',
            'hour_meter.min' => 'Hour meter tidak boleh minus.',
        ];
    }
}
