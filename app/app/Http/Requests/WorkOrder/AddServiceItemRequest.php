<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class AddServiceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service-orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'exists:services,id'],
            'qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'Jasa servis wajib dipilih.',
            'service_id.exists' => 'Jasa servis tidak valid.',
            'qty.required' => 'Kuantitas jasa wajib diisi.',
            'qty.min' => 'Kuantitas minimal 1.',
        ];
    }
}
