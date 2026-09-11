@extends('layouts.app')
@section('title', 'Yuki Official - My Page 設定')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            <h1 class="database-title" style="text-align: center;">My Page</h1>
            <p class="database-subtitle" style="text-align: center; margin-top: 8px; margin-bottom: 0;">アカウント設定</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <h2 class="section-title" style="font-size: 1.2rem;">プロフィール</h2>

                @if ($errors->profile->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->profile->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('mypage.settings.profile') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name" class="form-label">お名前</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', auth('external')->user()->name) }}">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', auth('external')->user()->email) }}" required>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">更新</button>
                </form>

                <h2 class="section-title" style="font-size: 1.2rem; margin-top: 40px;">パスワード変更</h2>

                @if ($errors->password->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->password->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('mypage.settings.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="current_password" class="form-label">現在のパスワード</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">新しいパスワード（8文字以上）</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">新しいパスワード（確認）</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">更新</button>
                </form>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="{{ route('mypage.index') }}" style="color: #888; font-size: 0.9rem;">
                        <i class="fa-solid fa-arrow-left"></i> Back to My Page
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
