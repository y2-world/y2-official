@extends('layouts.app')
@section('title', 'Yuki Official - My Page 新規登録')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            <h1 class="database-title" style="text-align: center;">My Page</h1>
            <p class="database-subtitle" style="text-align: center; margin-top: 8px; margin-bottom: 0;">新規登録</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('mypage.register') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">ユーザ名（任意）</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード（8文字以上）</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">パスワード（確認）</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">登録</button>
                </form>

                <p class="text-center" style="margin-top: 20px;">
                    すでにアカウントをお持ちの方は <a href="{{ route('mypage.login') }}" style="color: #667eea;">ログイン</a>
                </p>
            </div>
        </div>
    </div>
@endsection
