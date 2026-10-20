<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service-orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'equipment_unit_id' => ['required', 'exists:equipment_units,id'],
            'complaint' => ['required', 'string', 'min:3'],
            'diagnosis' => ['nullable', 'string'],
            'mechanic_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer wajib dipilih.',
            'customer_id.exists' => 'Customer tidak valid.',
            'equipment_unit_id.required' => 'Unit alat berat wajib dipilih.',
            'equipment_unit_id.exists' => 'Unit alat berat tidak valid.',
            'complaint.required' => 'Keluhan / deskripsi masalah wajib diisi.',
            'complaint.min' => 'Keluhan minimal 3 karakter.',
            'mechanic_id.exists' => 'Mekanik yang dipilih tidak valid.',
        ];
    }
}
