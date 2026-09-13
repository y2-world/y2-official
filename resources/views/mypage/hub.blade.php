@extends('layouts.app')

@section('title', 'My Page')
@section('og_title', 'My Page - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="element js-fadein">
                    <div style="text-align: center;">
                        <h1 class="stats-title" style="margin-bottom: 0;">My Page</h1>
                        @if (Auth::guard('external')->user()->name)
                            <p class="stats-subtitle" style="margin-top: 4px;">{{ Auth::guard('external')->user()->name }}</p>
                        @endif
                    </div>

                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; margin-top: 32px;">
                        <span style="color: rgba(255, 255, 255, 0.9); font-size: 0.85rem;">セットリストを追加</span>
                        <a href="{{ route('mypage.attendances.create') }}" class="mypage-add-button" title="セットリストを追加">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>

                    <div class="select-card-list" style="margin-top: 12px;">
                        <a href="#" class="select-card" title="準備中">
                            <span class="select-card-body">
                                <span class="select-card-title"><i class="fa-solid fa-stream" style="margin-right: 10px;"></i> Timeline</span>
                            </span>
                            <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                        </a>

                        <a href="{{ route('mypage.stats') }}" class="select-card">
                            <span class="select-card-body">
                                <span class="select-card-title"><i class="fa-solid fa-chart-simple" style="margin-right: 10px;"></i> My Statistics</span>
                            </span>
                            <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                        </a>

                        <a href="{{ route('mypage.stamps.index') }}" class="select-card">
                            <span class="select-card-body">
                                <span class="select-card-title"><i class="fa-solid fa-stamp" style="margin-right: 10px;"></i> My Stamp Books</span>
                            </span>
                            <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                        </a>

                        <a href="#" class="select-card" title="準備中">
                            <span class="select-card-body">
                                <span class="select-card-title"><i class="fa-solid fa-pen-to-square" style="margin-right: 10px;"></i> Manage My Artists & Setlists</span>
                            </span>
                            <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                        </a>

                        <a href="{{ route('mypage.settings') }}" class="select-card">
                            <span class="select-card-body">
                                <span class="select-card-title"><i class="fa-solid fa-gear" style="margin-right: 10px;"></i> Settings</span>
                            </span>
                            <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                        </a>
                    </div>

                    <div style="text-align: center; margin-top: 40px;">
                        <form method="POST" action="{{ route('mypage.logout') }}">
                            @csrf
                            <button type="submit" class="stamp-book-link" style="border: none; cursor: pointer;">ログアウト</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
