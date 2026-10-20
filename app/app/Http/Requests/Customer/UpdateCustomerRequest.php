<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master-data.manage') ?? false;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('customers', 'code')->ignore($customer)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:company,individual'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'payment_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode pelanggan wajib diisi.',
            'code.unique' => 'Kode pelanggan sudah digunakan.',
            'name.required' => 'Nama pelanggan wajib diisi.',
            'type.required' => 'Tipe pelanggan wajib dipilih.',
            'type.in' => 'Tipe pelanggan harus Perusahaan (company) atau Perorangan (individual).',
            'payment_term_days.required' => 'Termin pembayaran wajib diisi (0 jika tunai).',
            'payment_term_days.integer' => 'Termin pembayaran harus berupa angka hari.',
            'payment_term_days.min' => 'Termin pembayaran minimal 0 hari.',
        ];
    }
}
