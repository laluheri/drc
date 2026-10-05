<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login', ['title' => 'Login Admin']);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string|max:100', 'password' => 'required|string']);
        $key = mb_strtolower($data['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['username' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.']);
        }
        $field = filter_var($data['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (! Auth::attempt([$field => $data['username'], 'password' => $data['password'], 'status' => 'active'], $request->boolean('remember'))) {
            RateLimiter::hit($key, 900);
            throw ValidationException::withMessages(['username' => 'Username atau kata sandi tidak sesuai.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->update(['last_login' => now()]);
        Audit::record('login', 'auth');

        return redirect()->intended('/admin/dashboard');
    }

    public function logout(Request $request)
    {
        Audit::record('logout', 'auth');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function forgotForm()
    {
        return view('auth.forgot', ['title' => 'Lupa Kata Sandi']);
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('success', 'Jika akun terdaftar, tautan reset akan dikirim.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset', ['title' => 'Reset Kata Sandi', 'token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->numbers()]]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('login')->with('success', 'Kata sandi berhasil diperbarui.');
    }

    public function profile()
    {
        return view('auth.profile', ['title' => 'Profil Saya']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['full_name' => 'required|string|max:100', 'email' => ['required', 'email', 'max:100', Rule::unique('users')->ignore($user->id)], 'current_password' => 'required|current_password', 'password' => ['nullable', 'confirmed', PasswordRule::min(12)->letters()->numbers()]]);
        unset($data['current_password']);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['remember_token'] = Str::random(60);
        }
        $user->forceFill($data)->save();
        Audit::record('profile_update', 'users', $user->id);

        return back()->with('success','Profil berhasil diperbarui.');
    }
}
