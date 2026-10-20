<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class TransitionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service-orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:in_progress,completed,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status target wajib dipilih.',
            'status.in' => 'Status target tidak valid.',
        ];
    }
}
