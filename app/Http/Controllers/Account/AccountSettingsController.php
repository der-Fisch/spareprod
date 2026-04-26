<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountProfile;
use App\Models\User;
use App\Models\UserCheckout;
use App\Services\AccountSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function __construct(
        protected AccountSettingsService $settingsService,
    ) {
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = AccountProfile::query()->firstOrCreate(['user_id' => $user->id]);

        if ($user->is_staff) {
            return view('account.admin_settings', [
                'profile' => $profile,
            ]);
        }

        $checkoutProfile = $this->settingsService->resolveCheckoutProfile($user);
        $activeTab = $this->settingsService->resolveActiveTab(
            (string) ($request->query('tab') ?: session('account_settings_tab', 'biodata'))
        );

        return view('account.settings', [
            'profile' => $profile,
            'checkoutProfile' => $checkoutProfile,
            'addresses' => $checkoutProfile->addresses()->orderByDesc('is_default')->latest('id')->get(),
            'paymentMethods' => $user->paymentMethods()->orderByDesc('is_default')->latest('id')->get(),
            'paymentProviderOptions' => $this->settingsService->paymentProviderOptions(),
            'activeModal' => session('active_modal', ''),
            'activeTab' => $activeTab,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $action = (string) $request->input('action');
        $user = $request->user();
        $profile = AccountProfile::query()->firstOrCreate(['user_id' => $user->id]);

        if ($user->is_staff) {
            return $this->updateAdminSettings($request, $user, $profile, $action);
        }

        $activeTab = $this->settingsService->resolveActiveTab((string) $request->input('active_tab', 'biodata'));
        $checkoutProfile = $this->settingsService->resolveCheckoutProfile($user);

        return match ($action) {
            'profile' => $this->updateCustomerProfile($request, $user, $profile, $checkoutProfile, $activeTab),
            'password' => $this->updateCustomerPassword($request, $user, $activeTab),
            'address_create' => $this->createAddress($request, $checkoutProfile, $activeTab),
            'address_update' => $this->updateAddress($request, $checkoutProfile, $activeTab),
            'address_delete' => $this->deleteAddress($request, $checkoutProfile, $activeTab),
            'address_default' => $this->setDefaultAddress($request, $checkoutProfile, $activeTab),
            'payment_create' => $this->createPaymentMethod($request, $user, $activeTab),
            'payment_update' => $this->updatePaymentMethod($request, $user, $activeTab),
            'payment_delete' => $this->deletePaymentMethod($request, $user, $activeTab),
            'payment_default' => $this->setDefaultPaymentMethod($request, $user, $activeTab),
            default => $this->redirectToCustomerSettings($activeTab),
        };
    }

    protected function updateCustomerProfile(
        Request $request,
        User $user,
        AccountProfile $profile,
        UserCheckout $checkoutProfile,
        string $activeTab
    ): RedirectResponse {
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
            return $this->redirectToCustomerSettings($activeTab)
                ->withErrors($validator, 'profile')
                ->withInput()
                ->with('active_modal', '');
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

        $this->settingsService->syncCheckoutProfileEmail($checkoutProfile, $user);

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Biodata akun berhasil diperbarui.');
    }

    protected function updateCustomerPassword(Request $request, User $user, string $activeTab): RedirectResponse
    {
        $validator = $this->passwordValidator($request, $user);

        if ($validator->fails()) {
            return $this->redirectToCustomerSettings($activeTab)
                ->withErrors($validator, 'password')
                ->withInput();
        }

        $user->update([
            'password' => $request->input('password'),
        ]);

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Password berhasil diperbarui.');
    }

    protected function createAddress(Request $request, UserCheckout $checkoutProfile, string $activeTab): RedirectResponse
    {
        $validator = $this->addressValidator($request);

        if ($validator->fails()) {
            return $this->redirectToCustomerSettings($activeTab)
                ->withErrors($validator, 'address_create')
                ->withInput()
                ->with('active_modal', 'address-create-modal');
        }

        $payload = $this->settingsService->normalizeAddressPayload($validator->validated(), $checkoutProfile);
        $address = $checkoutProfile->addresses()->create($payload);
        $this->settingsService->applyDefaultAddress($checkoutProfile, $address, $payload['is_default']);

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Alamat berhasil ditambahkan.');
    }

    protected function updateAddress(Request $request, UserCheckout $checkoutProfile, string $activeTab): RedirectResponse
    {
        $address = $checkoutProfile->addresses()->whereKey($request->input('address_id'))->firstOrFail();
        $validator = $this->addressValidator($request);
        $errorBag = 'address_update_' . $address->id;

        if ($validator->fails()) {
            return $this->redirectToCustomerSettings($activeTab)
                ->withErrors($validator, $errorBag)
                ->withInput()
                ->with('active_modal', 'address-edit-modal-' . $address->id);
        }

        $payload = $this->settingsService->normalizeAddressPayload($validator->validated(), $checkoutProfile);
        $address->update($payload);
        $this->settingsService->applyDefaultAddress($checkoutProfile, $address, $payload['is_default']);

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Alamat berhasil diperbarui.');
    }

    protected function deleteAddress(Request $request, UserCheckout $checkoutProfile, string $activeTab): RedirectResponse
    {
        $address = $checkoutProfile->addresses()->whereKey($request->input('address_id'))->firstOrFail();
        $deletedWasDefault = $address->is_default;
        $address->delete();

        if ($deletedWasDefault) {
            $nextDefault = $checkoutProfile->addresses()->latest('id')->first();
            if ($nextDefault) {
                $nextDefault->forceFill(['is_default' => true])->save();
            }
        }

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Alamat berhasil dihapus.');
    }

    protected function setDefaultAddress(Request $request, UserCheckout $checkoutProfile, string $activeTab): RedirectResponse
    {
        $address = $checkoutProfile->addresses()->whereKey($request->input('address_id'))->firstOrFail();
        $this->settingsService->applyDefaultAddress($checkoutProfile, $address, true);

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Alamat utama berhasil diperbarui.');
    }

    protected function createPaymentMethod(Request $request, User $user, string $activeTab): RedirectResponse
    {
        $validator = $this->paymentValidator($request);

        if ($validator->fails()) {
            return $this->redirectToCustomerSettings($activeTab)
                ->withErrors($validator, 'payment_create')
                ->withInput()
                ->with('active_modal', 'payment-create-modal');
        }

        $payload = $this->settingsService->normalizePaymentPayload($validator->validated(), $user);
        $method = $user->paymentMethods()->create($payload);
        $this->settingsService->applyDefaultPaymentMethod($user, $method, $payload['is_default']);

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    protected function updatePaymentMethod(Request $request, User $user, string $activeTab): RedirectResponse
    {
        $method = $user->paymentMethods()->whereKey($request->input('payment_method_id'))->firstOrFail();
        $validator = $this->paymentValidator($request);
        $errorBag = 'payment_update_' . $method->id;

        if ($validator->fails()) {
            return $this->redirectToCustomerSettings($activeTab)
                ->withErrors($validator, $errorBag)
                ->withInput()
                ->with('active_modal', 'payment-edit-modal-' . $method->id);
        }

        $payload = $this->settingsService->normalizePaymentPayload($validator->validated(), $user);
        $method->update($payload);
        $this->settingsService->applyDefaultPaymentMethod($user, $method, $payload['is_default']);

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    protected function deletePaymentMethod(Request $request, User $user, string $activeTab): RedirectResponse
    {
        $method = $user->paymentMethods()->whereKey($request->input('payment_method_id'))->firstOrFail();
        $deletedWasDefault = $method->is_default;
        $method->delete();

        if ($deletedWasDefault) {
            $nextDefault = $user->paymentMethods()->latest('id')->first();
            if ($nextDefault) {
                $nextDefault->forceFill(['is_default' => true])->save();
            }
        }

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Metode pembayaran berhasil dihapus.');
    }

    protected function setDefaultPaymentMethod(Request $request, User $user, string $activeTab): RedirectResponse
    {
        $method = $user->paymentMethods()->whereKey($request->input('payment_method_id'))->firstOrFail();
        $this->settingsService->applyDefaultPaymentMethod($user, $method, true);

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Metode pembayaran utama berhasil diperbarui.');
    }

    protected function updateAdminSettings(Request $request, User $user, AccountProfile $profile, string $action): RedirectResponse
    {
        if ($action === 'profile') {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string', 'max:150', 'unique:users,username,' . $user->id],
                'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'whatsapp_number' => ['nullable', 'string', 'max:32'],
            ], [
                'username.unique' => 'Username sudah digunakan.',
                'email.unique' => 'Email sudah digunakan.',
            ]);

            if ($validator->fails()) {
                return redirect()->route('account.settings')
                    ->withErrors($validator, 'profile')
                    ->withInput();
            }

            $validated = $validator->validated();

            $user->update([
                'username' => $validated['username'],
                'email' => $validated['email'],
            ]);

            $profile->update([
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            ]);

            return redirect()->route('account.settings')
                ->with('success', 'Profil admin berhasil diperbarui.');
        }

        if ($action === 'password') {
            $validator = $this->passwordValidator($request, $user);

            if ($validator->fails()) {
                return redirect()->route('account.settings')
                    ->withErrors($validator, 'password')
                    ->withInput();
            }

            $user->update([
                'password' => $request->input('password'),
            ]);

            return redirect()->route('account.settings')
                ->with('success', 'Password admin berhasil diperbarui.');
        }

        return redirect()->route('account.settings');
    }

    protected function passwordValidator(Request $request, User $user)
    {
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

        return $validator;
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

    protected function paymentValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'provider_code' => ['required', Rule::in(array_keys($this->settingsService->paymentProviderOptions()))],
            'account_name' => ['nullable', 'string', 'max:120'],
            'account_reference' => ['nullable', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    protected function redirectToCustomerSettings(string $activeTab): RedirectResponse
    {
        return redirect()->route('account.settings', ['tab' => $activeTab])
            ->with('account_settings_tab', $activeTab);
    }
}
