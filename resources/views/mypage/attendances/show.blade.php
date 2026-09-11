@extends('layouts.app')
@section('title', 'Yuki Official - ' . ($attendance->dbSetlist->tour->title ?? '参加記録'))

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $attendance->dbSetlist->tour->title ?? '参加記録'],
            ]])
            <p class="database-subtitle" style="">
                {{ $attendance->dbSetlist->tour->artist->name ?? '' }}
            </p>
            <h1 class="database-title" style="">{{ $attendance->dbSetlist->tour->title ?? '' }}</h1>
            <p class="database-subtitle" style="">
                @if ($attendance->attended_date)
                    {{ $attendance->attended_date->format('Y.m.d') }}
                @endif
                <br>
                {{ $attendance->venue }}
            </p>
        </div>
    </div>

    <div class="container database-year-content">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="setlist" style="width: 100%;">
                    @include('db_concerts._setlist_rows', ['tourSetlists' => $tourSetlists, 'songs' => $songs])
                </div>
            </div>
        </div>
    </div>

    <div class="container database-year-content" style="padding-top: 0;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <a href="{{ route('mypage.index') }}">← My Pageに戻る</a>
            </div>
        </div>
    </div>
@endsection
