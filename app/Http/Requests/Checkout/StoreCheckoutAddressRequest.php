<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:80'],
            'nama_penerima' => ['required', 'string', 'max:120'],
            'nomor_whatsapp' => ['required', 'string', 'max:32'],
            'nama_jalan' => ['required', 'string', 'max:255'],
            'nama_kota' => ['required', 'string', 'max:255'],
            'negara' => ['required', 'string', 'max:255'],
            'kode_pos' => ['required', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}

