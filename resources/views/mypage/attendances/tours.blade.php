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

                @if ($canAddTour)
                    <div style="margin-top: 24px; text-align: center;">
                        <a href="#" id="newTourToggle" class="mypage-add-button" title="新しいツアーを追加" style="display: inline-flex;" @if(!$errors->any()) onclick="event.preventDefault(); document.getElementById('newTourForm').hidden = false; this.hidden = true; var msg = document.getElementById('noToursMessage'); if (msg) { msg.hidden = true; }" @else hidden @endif>
                            <i class="fas fa-plus"></i>
                        </a>
                        <form id="newTourForm" method="POST" action="{{ route('mypage.attendances.tours.new', $artistId) }}" @if(!$errors->any()) hidden @endif style="max-width: 360px; margin: 16px auto 0; text-align: left;">
                            @csrf
                            @if ($artistId === 'new')
                                <input type="hidden" name="name" value="{{ $artistName }}">
                            @endif
                            <div class="mb-3">
                                <label for="new_tour_title" class="form-label">ツアー名</label>
                                <input type="text" class="form-control" id="new_tour_title" name="title" value="{{ old('title') }}" required>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="new_tour_is_fes" name="is_fes" value="1" @if(old('is_fes')) checked @endif>
                                <label class="form-check-label" for="new_tour_is_fes">
                                    フェス・複数アーティスト出演イベント
                                </label>
                            </div>
                            <div class="mb-3">
                                <label for="new_tour_date1" class="form-label">開始日（任意）</label>
                                <input type="date" class="form-control" id="new_tour_date1" name="date1" value="{{ old('date1') }}">
                            </div>
                            <div class="mb-3">
                                <label for="new_tour_date2" class="form-label">終了日（任意・単発の場合は空欄）</label>
                                <input type="date" class="form-control" id="new_tour_date2" name="date2" value="{{ old('date2') }}">
                            </div>
                            <button type="submit" class="btn btn-outline-dark w-100">追加</button>
                            <div style="text-align: center; margin-top: 8px;">
                                <button type="button" onclick="document.getElementById('newTourForm').hidden = true; document.getElementById('newTourToggle').hidden = false; var msg = document.getElementById('noToursMessage'); if (msg) { msg.hidden = false; }" style="background: none; border: none; color: #999; cursor: pointer; padding: 20px;" title="閉じる">
                                    <i class="fa-solid fa-xmark" style="font-size: 40px;"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
