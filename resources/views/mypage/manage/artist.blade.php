@extends('layouts.app')

@section('title', $artist->name . ' - Manage My Artists & Setlists')
@section('og_title', $artist->name . ' - Manage My Artists & Setlists - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists', 'url' => route('mypage.manage.index')],
                ['label' => $artist->name],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">Manage My Artists & Setlists</p>
            <h1 class="database-title" style="text-align: center;">{{ $artist->name }}</h1>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="select-card-list">
                    <a href="{{ route('mypage.manage.songs', $artist->id) }}" class="select-card">
                        <span class="select-card-body">
                            <span class="select-card-title"><i class="fa-solid fa-music" style="margin-right: 10px;"></i> 曲を管理</span>
                            <span class="select-card-meta">{{ $songsCount }}曲</span>
                        </span>
                        <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                    </a>

                    <a href="{{ route('mypage.manage.concerts', $artist->id) }}" class="select-card">
                        <span class="select-card-body">
                            <span class="select-card-title"><i class="fa-solid fa-calendar-check" style="margin-right: 10px;"></i> ツアーを管理</span>
                            <span class="select-card-meta">{{ $concertsCount }}ツアー</span>
                        </span>
                        <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
