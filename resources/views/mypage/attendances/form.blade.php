@extends('layouts.app')
@section('title', 'Yuki Official - 参加日・会場を入力')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'ライブの参加記録を追加', 'url' => route('mypage.attendances.create')],
                ['label' => $dbSetlist->tour->artist->name, 'url' => route('mypage.attendances.tours', $dbSetlist->tour->artist_id)],
                ['label' => $dbSetlist->tour->title, 'url' => route('mypage.attendances.setlists', $dbSetlist->tour_id)],
                ['label' => '参加日・会場を入力'],
            ]])
            <h1 class="database-title" style="text-align: center;">参加日・会場を入力</h1>
            <p class="database-subtitle" style="text-align: center;">{{ $dbSetlist->tour->title }} - {{ $dbSetlist->subtitle ?: 'パターン ' . $dbSetlist->order_no }}</p>
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

                <form method="POST" action="{{ route('mypage.attendances.store') }}">
                    @csrf
                    <input type="hidden" name="db_setlist_id" value="{{ $dbSetlist->id }}">
                    <div class="mb-3">
                        <label for="attended_date" class="form-label">参加日</label>
                        <input type="date" class="form-control" id="attended_date" name="attended_date" value="{{ old('attended_date') }}">
                    </div>
                    <div class="mb-3">
                        <label for="venue" class="form-label">会場</label>
                        <input type="text" class="form-control" id="venue" name="venue" value="{{ old('venue', $dbSetlist->tour->venue) }}">
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">この記録を追加する</button>
                </form>
            </div>
        </div>
    </div>
@endsection
