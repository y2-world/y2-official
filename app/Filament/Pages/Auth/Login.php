<?php

namespace App\Filament\Pages\Auth;

use App\Models\ExternalUser;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// 管理画面のログイン。管理者のアカウントで入れなければ、マイページのアカウント（メールアドレスとパスワード）で入る。
// マイページのアカウントで入ったときは、そのアカウントに結び付いた管理画面用の users の行を使う（無ければ作る）
class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $remember = $data['remember'] ?? false;

        if (!Filament::auth()->attempt($this->getCredentialsFromFormData($data), $remember)) {
            $member = ExternalUser::where('email', $data['email'])->first();
            if (!$member || !Hash::check($data['password'], $member->password)) {
                $this->throwFailureValidationException();
            }
            Filament::auth()->login($this->adminUserFor($member), $remember);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    // マイページのアカウントに結び付いた管理画面用のアカウント。メールアドレスは管理者のものとぶつからないよう別にし、
    // パスワードは使わない（ログインは必ずマイページのアカウントで確かめる）
    private function adminUserFor(ExternalUser $member): User
    {
        $user = User::firstOrNew(['external_user_id' => $member->id]);
        $user->name = $member->name;
        if (!$user->exists) {
            $user->email = "mypage-{$member->id}@members.invalid";
            $user->password = Str::random(64);
        }
        $user->save();

        return $user;
    }
}
