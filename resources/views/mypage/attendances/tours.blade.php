@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artistName . ' ツアーを選択')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'セットリスト登録', 'url' => route('mypage.attendances.create')],
                ['label' => $artistName],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">セットリスト登録</p>
            <h1 class="database-title" style="text-align: center;">{{ $artistName }}</h1>
            <p class="database-subtitle" style="text-align: center;">ツアーを選択</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $tourKind = $artistId === 'new' ? 'user' : explode('-', $artistId, 2)[0];
                @endphp


                <div id="tourListSection">
                @if ($tours->isEmpty())
                    <p id="noToursMessage">このアーティストにはまだツアーが登録されていません。</p>
                @else
                    <div class="select-card-list">
                        @foreach ($tours as $tour)
                            <a href="{{ route('mypage.attendances.setlists', $tourKind . '-' . $tour->id) }}" class="select-card">
                                <span class="select-card-body">
                                    <span class="select-card-title">{{ $tour->title }}</span>
                                    <span class="select-card-meta">
                                        @if ($tour->date1 && $tour->date2)
                                            {{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}
                                        @elseif ($tour->date1)
                                            {{ date('Y.m.d', strtotime($tour->date1)) }}
                                        @endif
                                    </span>
                                </span>
                                <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
                </div>
            </div>
        </div>
    </div>
@endsection
