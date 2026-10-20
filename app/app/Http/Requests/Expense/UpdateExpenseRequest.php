<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'category.required' => 'Kategori pengeluaran wajib dipilih/diisi.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.min' => 'Nominal pengeluaran harus lebih besar dari 0.',
        ];
    }
}
