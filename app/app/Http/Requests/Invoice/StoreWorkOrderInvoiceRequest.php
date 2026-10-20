<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('invoices.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'issue_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'discount_amount.integer' => 'Nilai diskon harus berupa angka.',
            'discount_amount.min' => 'Nilai diskon tidak boleh negatif.',
        ];
    }
}
