@extends('layouts.app')
@section('title', 'Yuki Official - 参加日・会場を入力')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @php
                $setlistCreateParams = ['artistId' => $artistId, 'tourId' => $tourId];
                if ($artistId === 'new') {
                    $setlistCreateParams['name'] = $artistName;
                }
                if ($tourId === 'new') {
                    $setlistCreateParams['title'] = $tourTitle;
                    $setlistCreateParams['date1'] = $tourDate1;
                    $setlistCreateParams['date2'] = $tourDate2;
                }
            @endphp
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'セットリスト登録', 'url' => route('mypage.attendances.create')],
                ['label' => $artistName, 'url' => route('mypage.attendances.tours', $artistId === 'new' ? ['artistId' => 'new', 'name' => $artistName] : $artistId)],
                ['label' => $tourTitle, 'url' => route('mypage.attendances.setlist_create', $setlistCreateParams)],
                ['label' => '参加日・会場を入力'],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">セットリスト登録</p>
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $artistName }}</p>
            <h1 class="database-title" style="text-align: center;">{{ $tourTitle }}</h1>
            <p class="database-subtitle" style="text-align: center;">参加日・会場を入力</p>
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

                <form method="POST" action="{{ route('mypage.attendances.setlist_create.store', ['artistId' => $artistId, 'tourId' => $tourId]) }}">
                    @csrf
                    @if ($artistId === 'new')
                        <input type="hidden" name="artist_name" value="{{ $artistName }}">
                    @endif
                    @if ($tourId === 'new')
                        <input type="hidden" name="tour_title" value="{{ $tourTitle }}">
                        <input type="hidden" name="tour_date1" value="{{ $tourDate1 }}">
                        <input type="hidden" name="tour_date2" value="{{ $tourDate2 }}">
                    @endif
                    @foreach ($setlist as $title)
                        <input type="hidden" name="setlist[]" value="{{ $title }}">
                    @endforeach
                    @foreach ($encore as $title)
                        <input type="hidden" name="encore[]" value="{{ $title }}">
                    @endforeach

                    <div class="mb-3">
                        <label for="attended_date" class="form-label">参加日</label>
                        <input type="date" class="form-control" id="attended_date" name="attended_date" value="{{ old('attended_date', $defaultAttendedDate) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="venue" class="form-label">会場</label>
                        <input type="text" class="form-control" id="venue" name="venue" value="{{ old('venue') }}" required>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="#" onclick="event.preventDefault(); history.back();" class="btn btn-outline-secondary w-100">戻る</a>
                        <button type="submit" class="btn btn-outline-dark w-100">登録</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
