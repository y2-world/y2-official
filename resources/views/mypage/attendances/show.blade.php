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
                @if ($attendance->dbSetlist->tour->artist)
                    <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}" style="color: white; text-decoration: none;">
                        {{ $attendance->dbSetlist->tour->artist->name }}
                    </a>
                @endif
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
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="setlist" style="width: 100%;">
                    @include('mypage.attendances._setlist_cards', ['setlistModel' => $attendance->dbSetlist, 'songs' => $songs])
                </div>
            </div>
        </div>
    </div>

    <div class="container database-year-content" style="padding-top: 0;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="{{ route('mypage.index') }}" style="color: #888; font-size: 0.9rem;">
                        <i class="fa-solid fa-arrow-left"></i> Back to My Page
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
