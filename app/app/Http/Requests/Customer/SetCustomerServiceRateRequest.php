<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class SetCustomerServiceRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master-data.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'exists:services,id'],
            'price' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'Jenis jasa wajib dipilih.',
            'service_id.exists' => 'Jasa yang dipilih tidak valid.',
            'price.required' => 'Tarif khusus jasa wajib diisi.',
            'price.integer' => 'Tarif khusus harus berupa angka integer rupiah.',
            'price.min' => 'Tarif khusus minimal Rp0.',
        ];
    }
}
