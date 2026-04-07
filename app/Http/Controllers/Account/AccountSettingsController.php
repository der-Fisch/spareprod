<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountProfile;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserCheckout;
use App\Models\UserPaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = AccountProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $checkoutProfile = $this->resolveCheckoutProfile($user);
        $activeTab = $this->resolveActiveTab((string) ($request->query('tab') ?: session('account_settings_tab', 'biodata')));

        return view('account.settings', [
            'profile' => $profile,
            'checkoutProfile' => $checkoutProfile,
            'addresses' => $checkoutProfile->addresses()->orderByDesc('is_default')->latest('id')->get(),
            'paymentMethods' => $user->paymentMethods()->orderByDesc('is_default')->latest('id')->get(),
            'paymentProviderOptions' => $this->paymentProviderOptions(),
            'activeModal' => session('active_modal', ''),
            'activeTab' => $activeTab,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $action = (string) $request->input('action');
        $activeTab = $this->resolveActiveTab((string) $request->input('active_tab', 'biodata'));
        $user = $request->user();
        $profile = AccountProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $checkoutProfile = $this->resolveCheckoutProfile($user);

        if ($action === 'profile') {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string', 'max:150', 'unique:users,username,' . $user->id],
                'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'first_name' => ['nullable', 'string', 'max:150'],
                'last_name' => ['nullable', 'string', 'max:150'],
                'phone_number' => ['nullable', 'string', 'max:32'],
                'whatsapp_number' => ['nullable', 'string', 'max:32'],
                'birth_date' => ['nullable', 'date'],
                'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            ], [
                'username.unique' => 'Username sudah digunakan.',
                'email.unique' => 'Email sudah digunakan.',
            ]);

            if ($validator->fails()) {
                return redirect()->route('account.settings', ['tab' => $activeTab])
                    ->withErrors($validator, 'profile')
                    ->withInput()
                    ->with('active_modal', '')
                    ->with('account_settings_tab', $activeTab);
            }

            $validated = $validator->validated();

            $user->update([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
            ]);

            $profile->update([
                'phone_number' => $validated['phone_number'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
            ]);

            $this->syncCheckoutProfileEmail($checkoutProfile, $user);

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Biodata akun berhasil diperbarui.')
                ->with('account_settings_tab', $activeTab);
        }

        if ($action === 'password') {
            $validator = Validator::make($request->all(), [
                'current_password' => ['required', 'string'],
                'password' => ['required', 'confirmed', 'min:8'],
            ], [
                'current_password.required' => 'Password lama wajib diisi.',
            ]);

            $validator->after(function ($validator) use ($request, $user) {
                if (! Hash::check((string) $request->input('current_password'), (string) $user->password)) {
                    $validator->errors()->add('current_password', 'Password lama tidak sesuai.');
                }
            });

            if ($validator->fails()) {
                return redirect()->route('account.settings', ['tab' => $activeTab])
                    ->withErrors($validator, 'password')
                    ->withInput()
                    ->with('account_settings_tab', $activeTab);
            }

            $user->update([
                'password' => $request->input('password'),
            ]);

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Password berhasil diperbarui.')
                ->with('account_settings_tab', $activeTab);
        }

        if ($action === 'address_create') {
            $validator = $this->addressValidator($request);

            if ($validator->fails()) {
                return redirect()->route('account.settings', ['tab' => $activeTab])
                    ->withErrors($validator, 'address_create')
                    ->withInput()
                    ->with('active_modal', 'address-create-modal')
                    ->with('account_settings_tab', $activeTab);
            }

            $payload = $this->normalizeAddressPayload($validator->validated(), $checkoutProfile);
            $address = $checkoutProfile->addresses()->create($payload);
            $this->applyDefaultAddress($checkoutProfile, $address, $payload['is_default']);

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Alamat berhasil ditambahkan.')
                ->with('account_settings_tab', $activeTab);
        }

        if ($action === 'address_update') {
            $address = $checkoutProfile->addresses()->whereKey($request->input('address_id'))->firstOrFail();
            $validator = $this->addressValidator($request);
            $errorBag = 'address_update_' . $address->id;

            if ($validator->fails()) {
                return redirect()->route('account.settings', ['tab' => $activeTab])
                    ->withErrors($validator, $errorBag)
                    ->withInput()
                    ->with('active_modal', 'address-edit-modal-' . $address->id)
                    ->with('account_settings_tab', $activeTab);
            }

            $payload = $this->normalizeAddressPayload($validator->validated(), $checkoutProfile);
            $address->update($payload);
            $this->applyDefaultAddress($checkoutProfile, $address, $payload['is_default']);

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Alamat berhasil diperbarui.')
                ->with('account_settings_tab', $activeTab);
        }

        if ($action === 'address_delete') {
            $address = $checkoutProfile->addresses()->whereKey($request->input('address_id'))->firstOrFail();
            $deletedWasDefault = $address->is_default;
            $address->delete();

            if ($deletedWasDefault) {
                $nextDefault = $checkoutProfile->addresses()->latest('id')->first();
                if ($nextDefault) {
                    $nextDefault->forceFill(['is_default' => true])->save();
                }
            }

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Alamat berhasil dihapus.')
                ->with('account_settings_tab', $activeTab);
        }

        if ($action === 'address_default') {
            $address = $checkoutProfile->addresses()->whereKey($request->input('address_id'))->firstOrFail();
            $this->applyDefaultAddress($checkoutProfile, $address, true);

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Alamat utama berhasil diperbarui.')
                ->with('account_settings_tab', $activeTab);
        }

        if ($action === 'payment_create') {
            $validator = $this->paymentValidator($request);

            if ($validator->fails()) {
                return redirect()->route('account.settings', ['tab' => $activeTab])
                    ->withErrors($validator, 'payment_create')
                    ->withInput()
                    ->with('active_modal', 'payment-create-modal')
                    ->with('account_settings_tab', $activeTab);
            }

            $payload = $this->normalizePaymentPayload($validator->validated(), $user);
            $method = $user->paymentMethods()->create($payload);
            $this->applyDefaultPaymentMethod($user, $method, $payload['is_default']);

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Metode pembayaran berhasil ditambahkan.')
                ->with('account_settings_tab', $activeTab);
        }

        if ($action === 'payment_update') {
            $method = $user->paymentMethods()->whereKey($request->input('payment_method_id'))->firstOrFail();
            $validator = $this->paymentValidator($request);
            $errorBag = 'payment_update_' . $method->id;

            if ($validator->fails()) {
                return redirect()->route('account.settings', ['tab' => $activeTab])
                    ->withErrors($validator, $errorBag)
                    ->withInput()
                    ->with('active_modal', 'payment-edit-modal-' . $method->id)
                    ->with('account_settings_tab', $activeTab);
            }

            $payload = $this->normalizePaymentPayload($validator->validated(), $user);
            $method->update($payload);
            $this->applyDefaultPaymentMethod($user, $method, $payload['is_default']);

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Metode pembayaran berhasil diperbarui.')
                ->with('account_settings_tab', $activeTab);
        }

        if ($action === 'payment_delete') {
            $method = $user->paymentMethods()->whereKey($request->input('payment_method_id'))->firstOrFail();
            $deletedWasDefault = $method->is_default;
            $method->delete();

            if ($deletedWasDefault) {
                $nextDefault = $user->paymentMethods()->latest('id')->first();
                if ($nextDefault) {
                    $nextDefault->forceFill(['is_default' => true])->save();
                }
            }

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Metode pembayaran berhasil dihapus.')
                ->with('account_settings_tab', $activeTab);
        }

        if ($action === 'payment_default') {
            $method = $user->paymentMethods()->whereKey($request->input('payment_method_id'))->firstOrFail();
            $this->applyDefaultPaymentMethod($user, $method, true);

            return redirect()->route('account.settings', ['tab' => $activeTab])
                ->with('success', 'Metode pembayaran utama berhasil diperbarui.')
                ->with('account_settings_tab', $activeTab);
        }

        return redirect()->route('account.settings', ['tab' => $activeTab])
            ->with('account_settings_tab', $activeTab);
    }

    protected function resolveCheckoutProfile(User $user): UserCheckout
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

    protected function syncCheckoutProfileEmail(UserCheckout $checkout, User $user): void
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

    protected function resolveActiveTab(string $tab): string
    {
        $allowedTabs = ['biodata', 'addresses', 'payments', 'security'];

        return in_array($tab, $allowedTabs, true) ? $tab : 'biodata';
    }

    protected function addressValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'label' => ['nullable', 'string', 'max:80'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:32'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zipcode' => ['required', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    protected function normalizeAddressPayload(array $validated, UserCheckout $checkout): array
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

    protected function applyDefaultAddress(UserCheckout $checkout, UserAddress $selectedAddress, bool $shouldBeDefault): void
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

    protected function paymentValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'provider_code' => ['required', Rule::in(array_keys($this->paymentProviderOptions()))],
            'account_name' => ['nullable', 'string', 'max:120'],
            'account_reference' => ['nullable', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    protected function normalizePaymentPayload(array $validated, User $user): array
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

    protected function applyDefaultPaymentMethod(User $user, UserPaymentMethod $selectedMethod, bool $shouldBeDefault): void
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

    protected function paymentProviderOptions(): array
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
}
