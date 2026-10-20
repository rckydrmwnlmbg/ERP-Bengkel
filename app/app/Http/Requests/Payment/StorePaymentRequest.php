<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date'],
            'method' => ['required', 'in:cash,transfer,other'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Jumlah pembayaran wajib diisi.',
            'amount.integer' => 'Jumlah pembayaran harus berupa angka.',
            'amount.min' => 'Jumlah pembayaran harus lebih besar dari 0 (BR-Y1).',
            'paid_at.required' => 'Tanggal pembayaran wajib diisi.',
            'method.required' => 'Metode pembayaran wajib dipilih.',
            'method.in' => 'Metode pembayaran tidak valid.',
        ];
    }
}
