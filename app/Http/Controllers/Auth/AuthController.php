<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccountProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    protected function resolveNext(?string $next): string
    {
        if (! $next) {
            return route('home');
        }

        return str_starts_with($next, url('/')) || str_starts_with($next, '/')
            ? $next
            : route('home');
    }

    public function showLogin(Request $request): View
    {
        return view('auth.login', [
            'next' => $this->resolveNext($request->query('next', url()->previous())),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'Username atau password tidak valid.'])
                ->withInput($request->except('password'));
        }

        $request->session()->regenerate();

        if ($request->user()?->is_staff) {
            return redirect()->route('backoffice.dashboard');
        }

        return redirect()->to($this->resolveNext($request->input('next')));
    }

    public function showRegister(Request $request): View
    {
        return view('auth.register', [
            'next' => $this->resolveNext($request->query('next')),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'username' => ['required', 'string', 'max:150', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::query()->create([
            'username' => $payload['username'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'date_joined' => now(),
        ]);

        AccountProfile::query()->firstOrCreate(['user_id' => $user->id]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($this->resolveNext($request->input('next')))
            ->with('success', 'Your account has been created.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
