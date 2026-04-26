<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserCheckout;

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
        $allowedTabs = ['biodata', 'addresses', 'security'];

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
}
