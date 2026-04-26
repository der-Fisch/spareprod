<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserCheckout;

class AccountSettingService
{
    public function resolveCheckoutProfile(User $user): UserCheckout
    {
        $checkoutProfile = UserCheckout::query()->where('user_id', $user->id)->first();

        if (! $checkoutProfile) {
            $checkoutProfile = UserCheckout::query()->where('email', $user->email)->first();
        }

        if (! $checkoutProfile) {
            $checkoutProfile = UserCheckout::query()->create([
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        if ($checkoutProfile->user_id !== $user->id) {
            $checkoutProfile->user_id = $user->id;
        }

        if (! $checkoutProfile->email) {
            $checkoutProfile->email = $user->email;
        }

        $checkoutProfile->save();

        return $checkoutProfile;
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

    public function normalizeAddressPayload(array $validatedData, UserCheckout $checkout): array
    {
        return [
            'label' => $validatedData['label'] ?? null,
            'nama_penerima' => $validatedData['nama_penerima'],
            'nomor_whatsapp' => $validatedData['nomor_whatsapp'],
            'tipe' => 'shipping',
            'nama_jalan' => $validatedData['nama_jalan'],
            'nama_kota' => $validatedData['nama_kota'],
            'negara' => $validatedData['negara'],
            'kode_pos' => $validatedData['kode_pos'],
            'is_default' => ! $checkout->addresses()->exists() || ! empty($validatedData['is_default']),
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
