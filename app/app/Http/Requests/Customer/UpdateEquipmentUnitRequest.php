<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master-data.manage') ?? false;
    }

    public function rules(): array
    {
        return [
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
            'brand.required' => 'Merek alat berat wajib diisi.',
            'model.required' => 'Model unit wajib diisi.',
            'hour_meter.integer' => 'Hour meter harus berupa angka bulat positif.',
            'hour_meter.min' => 'Hour meter tidak boleh minus.',
        ];
    }
}
