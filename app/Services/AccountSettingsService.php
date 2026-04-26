<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserCheckout;
use App\Models\UserPaymentMethod;

class AccountSettingsService
{
    public function resolveCheckoutProfile(User $user): UserCheckout
    {
        $checkout = UserCheckout::query()->where('user_id', $user->id)->first();

        if (! $checkout) {
            $checkout = UserCheckout::query()->where('email', $user->email)->first();
        }

        if (! $checkout) {
            $checkout = UserCheckout::query()->create([
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        if ($checkout->user_id !== $user->id) {
            $checkout->user_id = $user->id;
        }

        if (! $checkout->email) {
            $checkout->email = $user->email;
        }

        $checkout->save();

        return $checkout;
    }

    public function syncCheckoutProfileEmail(UserCheckout $checkout, User $user): void
    {
        $emailConflict = UserCheckout::query()
            ->where('email', $user->email)
            ->whereKeyNot($checkout->id)
            ->exists();

        if ($emailConflict) {
            return;
        }

        $checkout->email = $user->email;
        $checkout->save();
    }

    public function resolveActiveTab(string $tab): string
    {
        $allowedTabs = ['biodata', 'addresses', 'payments', 'security'];

        return in_array($tab, $allowedTabs, true) ? $tab : 'biodata';
    }

    public function normalizeAddressPayload(array $validated, UserCheckout $checkout): array
    {
        return [
            'label' => $validated['label'] ?? null,
            'recipient_name' => $validated['recipient_name'],
            'phone_number' => $validated['phone_number'],
            'type' => 'shipping',
            'street' => $validated['street'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zipcode' => $validated['zipcode'],
            'is_default' => ! $checkout->addresses()->exists() || ! empty($validated['is_default']),
        ];
    }

    public function applyDefaultAddress(UserCheckout $checkout, UserAddress $selectedAddress, bool $shouldBeDefault): void
    {
        if (! $shouldBeDefault) {
            if (! $checkout->addresses()->where('is_default', true)->exists()) {
                $selectedAddress->forceFill(['is_default' => true])->save();
            }

            return;
        }

        $checkout->addresses()->whereKeyNot($selectedAddress->id)->update(['is_default' => false]);
        $selectedAddress->forceFill(['is_default' => true])->save();
    }

    public function paymentProviderOptions(): array
    {
        return [
            'bca_va' => [
                'name' => 'BCA Virtual Account',
                'type' => 'virtual_account',
                'description' => 'Pembayaran virtual account BCA untuk checkout.',
            ],
            'bri_va' => [
                'name' => 'BRI Virtual Account',
                'type' => 'virtual_account',
                'description' => 'Pembayaran virtual account BRI untuk checkout.',
            ],
            'mandiri_va' => [
                'name' => 'Mandiri Virtual Account',
                'type' => 'virtual_account',
                'description' => 'Pembayaran virtual account Mandiri untuk checkout.',
            ],
            'gopay' => [
                'name' => 'GoPay',
                'type' => 'ewallet',
                'description' => 'Dompet digital untuk pembayaran yang cepat dan praktis.',
            ],
            'qris' => [
                'name' => 'QRIS',
                'type' => 'qris',
                'description' => 'Pembayaran QRIS yang fleksibel untuk berbagai aplikasi.',
            ],
            'alfamart' => [
                'name' => 'Alfamart / Alfamidi / Lawson / Dan+Dan',
                'type' => 'retail',
                'description' => 'Pembayaran melalui gerai retail yang bekerja sama.',
            ],
        ];
    }

    public function normalizePaymentPayload(array $validated, User $user): array
    {
        $provider = $this->paymentProviderOptions()[$validated['provider_code']];

        return [
            'provider_code' => $validated['provider_code'],
            'provider_name' => $provider['name'],
            'method_type' => $provider['type'],
            'account_name' => $validated['account_name'] ?? $user->name,
            'account_reference' => $validated['account_reference'] ?? null,
            'status' => 'demo_ready',
            'is_default' => ! $user->paymentMethods()->exists() || ! empty($validated['is_default']),
        ];
    }

    public function applyDefaultPaymentMethod(User $user, UserPaymentMethod $selectedMethod, bool $shouldBeDefault): void
    {
        if (! $shouldBeDefault) {
            if (! $user->paymentMethods()->where('is_default', true)->exists()) {
                $selectedMethod->forceFill(['is_default' => true])->save();
            }

            return;
        }

        $user->paymentMethods()->whereKeyNot($selectedMethod->id)->update(['is_default' => false]);
        $selectedMethod->forceFill(['is_default' => true])->save();
    }
}
