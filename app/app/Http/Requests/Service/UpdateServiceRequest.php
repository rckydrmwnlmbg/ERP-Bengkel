<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master-data.manage') ?? false;
    }

    public function rules(): array
    {
        $service = $this->route('service');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('services', 'code')->ignore($service)],
            'name' => ['required', 'string', 'max:255'],
            'default_price' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode jasa wajib diisi.',
            'code.unique' => 'Kode jasa sudah terdaftar.',
            'name.required' => 'Nama jasa wajib diisi.',
            'default_price.required' => 'Tarif standar jasa wajib diisi.',
            'default_price.integer' => 'Tarif standar harus berupa integer rupiah.',
            'default_price.min' => 'Tarif standar minimal Rp0.',
        ];
    }
}
