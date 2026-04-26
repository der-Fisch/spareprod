<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountProfile;
use App\Models\User;
use App\Models\UserCheckout;
use App\Services\AccountSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserSettingController extends Controller
{
    public function __construct(
        protected AccountSettingService $accountSettingService,
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

        $checkoutProfile = $this->accountSettingService->resolveCheckoutProfile($user);
        $activeTab = $this->accountSettingService->resolveActiveTab(
            (string) ($request->query('tab') ?: session('account_settings_tab', 'biodata'))
        );

        return view('account.settings', [
            'profile' => $profile,
            'checkoutProfile' => $checkoutProfile,
            'addresses' => $checkoutProfile->addresses()->orderByDesc('is_default')->latest('id')->get(),
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

        $activeTab = $this->accountSettingService->resolveActiveTab((string) $request->input('active_tab', 'biodata'));
        $checkoutProfile = $this->accountSettingService->resolveCheckoutProfile($user);

        return match ($action) {
            'profile' => $this->updateCustomerProfile($request, $user, $profile, $checkoutProfile, $activeTab),
            'password' => $this->updateCustomerPassword($request, $user, $activeTab),
            'address_create' => $this->createAddress($request, $checkoutProfile, $activeTab),
            'address_update' => $this->updateAddress($request, $checkoutProfile, $activeTab),
            'address_delete' => $this->deleteAddress($request, $checkoutProfile, $activeTab),
            'address_default' => $this->setDefaultAddress($request, $checkoutProfile, $activeTab),
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
            'nama_depan' => ['nullable', 'string', 'max:150'],
            'nama_belakang' => ['nullable', 'string', 'max:150'],
            'nomor_whatsapp' => ['nullable', 'string', 'max:32'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', Rule::in(['male', 'female', 'other'])],
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
            'nama_depan' => $validated['nama_depan'] ?? null,
            'nama_belakang' => $validated['nama_belakang'] ?? null,
        ]);

        $profile->update([
            'nomor_whatsapp' => $validated['nomor_whatsapp'] ?? null,
            'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
        ]);

        $this->accountSettingService->syncCheckoutProfileEmail($checkoutProfile, $user);

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

        $payload = $this->accountSettingService->normalizeAddressPayload($validator->validated(), $checkoutProfile);
        $address = $checkoutProfile->addresses()->create($payload);
        $this->accountSettingService->applyDefaultAddress($checkoutProfile, $address, $payload['is_default']);

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

        $payload = $this->accountSettingService->normalizeAddressPayload($validator->validated(), $checkoutProfile);
        $address->update($payload);
        $this->accountSettingService->applyDefaultAddress($checkoutProfile, $address, $payload['is_default']);

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
        $this->accountSettingService->applyDefaultAddress($checkoutProfile, $address, true);

        return $this->redirectToCustomerSettings($activeTab)
            ->with('success', 'Alamat utama berhasil diperbarui.');
    }

    protected function updateAdminSettings(Request $request, User $user, AccountProfile $profile, string $action): RedirectResponse
    {
        if ($action === 'profile') {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string', 'max:150', 'unique:users,username,' . $user->id],
                'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'nomor_whatsapp' => ['nullable', 'string', 'max:32'],
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
                'nomor_whatsapp' => $validated['nomor_whatsapp'] ?? $validated['whatsapp_number'] ?? null,
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
            'nama_penerima' => ['required', 'string', 'max:120'],
            'nomor_whatsapp' => ['required', 'string', 'max:32'],
            'nama_jalan' => ['required', 'string', 'max:255'],
            'nama_kota' => ['required', 'string', 'max:255'],
            'negara' => ['required', 'string', 'max:255'],
            'kode_pos' => ['required', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    protected function redirectToCustomerSettings(string $activeTab): RedirectResponse
    {
        return redirect()->route('account.settings', ['tab' => $activeTab])
            ->with('account_settings_tab', $activeTab);
    }
}
