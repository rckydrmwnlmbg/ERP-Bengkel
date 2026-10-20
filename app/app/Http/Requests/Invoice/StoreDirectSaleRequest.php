<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class StoreDirectSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('direct-sales.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'issue_date' => ['nullable', 'date'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_id' => ['required', 'exists:parts,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.override_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer wajib dipilih.',
            'items.required' => 'Minimal 1 item sparepart harus dipilih.',
            'items.min' => 'Minimal 1 item sparepart harus dipilih.',
            'items.*.part_id.required' => 'Sparepart wajib dipilih.',
            'items.*.qty.required' => 'Jumlah sparepart wajib diisi.',
            'items.*.qty.min' => 'Jumlah sparepart minimal 1 unit.',
        ];
    }
}
