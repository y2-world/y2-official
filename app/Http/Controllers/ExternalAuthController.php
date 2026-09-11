<?php

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ExternalAuthController extends Controller
{
    public function showRegister()
    {
        return view('mypage.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:external_users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = ExternalUser::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::guard('external')->login($user);
        $request->session()->regenerate();

        return redirect()->route('mypage.index');
    }

    public function showLogin()
    {
        return view('mypage.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('external')->attempt($credentials)) {
            return back()->withErrors([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('mypage.index'));
    }

    public function logout(Request $request)
    {
        Auth::guard('external')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('mypage.login');
    }
}
