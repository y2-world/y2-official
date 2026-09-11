@extends('layouts.app')
@section('title', 'Yuki Official - 参加記録を編集')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => '参加記録を編集'],
            ]])
            <h1 class="database-title" style="text-align: center;">参加記録を編集</h1>
            <p class="database-subtitle" style="text-align: center;">
                {{ $attendance->dbSetlist->tour->title ?? '-' }}
                @if ($attendance->dbSetlist?->subtitle)
                    - {{ $attendance->dbSetlist->subtitle }}
                @endif
            </p>
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

                <form method="POST" action="{{ route('mypage.attendances.update', $attendance) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="attended_date" class="form-label">参加日</label>
                        <input type="date" class="form-control" id="attended_date" name="attended_date" value="{{ old('attended_date', $attendance->attended_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="mb-3">
                        <label for="venue" class="form-label">会場</label>
                        <input type="text" class="form-control" id="venue" name="venue" value="{{ old('venue', $attendance->venue) }}">
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">更新</button>
                </form>
            </div>
        </div>
    </div>
@endsection
