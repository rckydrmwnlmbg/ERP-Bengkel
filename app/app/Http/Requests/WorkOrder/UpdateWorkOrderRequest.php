<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service-orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'complaint' => ['required', 'string', 'min:3'],
            'diagnosis' => ['nullable', 'string'],
            'mechanic_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'complaint.required' => 'Keluhan wajib diisi.',
            'complaint.min' => 'Keluhan minimal 3 karakter.',
            'mechanic_id.exists' => 'Mekanik tidak valid.',
        ];
    }
}
