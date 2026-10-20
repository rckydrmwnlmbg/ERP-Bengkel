<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class AddPartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service-orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'part_id' => ['required', 'exists:parts,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'override_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'part_id.required' => 'Sparepart wajib dipilih.',
            'part_id.exists' => 'Sparepart tidak valid.',
            'qty.required' => 'Kuantitas sparepart wajib diisi.',
            'qty.min' => 'Kuantitas minimal 1 unit.',
        ];
    }
}
