<?php

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    public function showSettings()
    {
        return view('mypage.settings');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('external')->user();

        $data = $request->validateWithBag('profile', [
            'name' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
            'email' => ['required', 'email', 'max:255', 'unique:external_users,email,' . $user->id],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ]);

        // 管理画面のOfficialProfileResource等と同じ方式（Cloudinary、ULIDでファイル名衝突を避ける）
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->storeAs(
                'avatars',
                (string) str()->ulid(),
                'cloudinary'
            );
            $data['avatar_url'] = Storage::disk('cloudinary')->url($path);
        }
        unset($data['avatar']);

        $user->update($data);

        return redirect()->route('mypage.settings')->with('success', 'プロフィールを更新しました。');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::guard('external')->user();

        $request->validateWithBag('password', [
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors([
                'current_password' => '現在のパスワードが正しくありません。',
            ], 'password');
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return redirect()->route('mypage.settings')->with('success', 'パスワードを更新しました。');
    }
}
