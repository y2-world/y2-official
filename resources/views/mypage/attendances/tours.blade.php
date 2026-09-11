@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' ツアーを選択')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'ライブの参加記録を追加', 'url' => route('mypage.attendances.create')],
                ['label' => $artist->name],
            ]])
            <h1 class="database-title" style="text-align: center;">{{ $artist->name }}</h1>
            <p class="database-subtitle" style="text-align: center;">ツアーを選択</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if ($tours->isEmpty())
                    <p>このアーティストにはまだツアーが登録されていません。</p>
                @else
                    <div class="select-card-list">
                        @foreach ($tours as $tour)
                            <a href="{{ route('mypage.attendances.setlists', $tour->id) }}" class="select-card">
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
@endsection
