<?php

namespace App\Http\Requests\Checkout;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class GuestCheckoutRequest extends FormRequest
{
    protected $errorBag = 'guest';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'email2' => [
                'required',
                'same:email',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (User::query()->where('email', $this->input('email'))->exists()) {
                        $fail('Akun dengan email ini sudah ada. Silakan login terlebih dahulu.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email2.same' => 'Konfirmasi email harus sama dengan email utama.',
        ];
    }
}

