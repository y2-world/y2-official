@extends('layouts.app')
@section('title', 'Yuki Official - My Page ログイン')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            <h1 class="database-title" style="text-align: center;">My Page</h1>
            <p class="database-subtitle" style="text-align: center; margin-top: 8px; margin-bottom: 0;">ログイン</p>
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

                <form method="POST" action="{{ route('mypage.login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">ログイン</button>
                </form>

                <a href="{{ route('mypage.register') }}" class="btn btn-outline-dark w-100" style="margin-top: 12px;">新規登録</a>
            </div>
        </div>
    </div>
@endsection
