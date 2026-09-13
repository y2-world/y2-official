@extends('layouts.app')
@section('title', 'Yuki Official - セットリストを入力')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'セットリスト登録', 'url' => route('mypage.attendances.create')],
                ['label' => $artistName, 'url' => route('mypage.attendances.tours', $artistId === 'new' ? ['artistId' => 'new', 'name' => $artistName] : $artistId)],
                ['label' => $tourTitle],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">セットリスト登録</p>
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $artistName }}</p>
            <h1 class="database-title" style="text-align: center;">{{ $tourTitle }}</h1>
            <p class="database-subtitle" style="text-align: center;">セットリストを入力してください</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <datalist id="songTitleOptions">
                    @foreach ($songOptions as $title)
                        <option value="{{ $title }}"></option>
                    @endforeach
                </datalist>

                <form method="POST" action="{{ route('mypage.attendances.setlist_create.confirm', ['artistId' => $artistId, 'tourId' => $tourId]) }}">
                    @csrf
                    @if ($artistId === 'new')
                        <input type="hidden" name="artist_name" value="{{ $artistName }}">
                    @endif
                    @if ($tourId === 'new')
                        <input type="hidden" name="tour_title" value="{{ $tourTitle }}">
                        <input type="hidden" name="tour_date1" value="{{ $tourDate1 }}">
                        <input type="hidden" name="tour_date2" value="{{ $tourDate2 }}">
                    @endif

                    <h5>本編</h5>
                    <div id="setlistRows" class="setlist-song-rows"></div>
                    <div style="text-align: center; margin-bottom: 24px;">
                        <button type="button" class="mypage-add-button" title="曲を追加" onclick="addSongRow('setlistRows', 'setlist')" style="border: none;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <h5>アンコール</h5>
                    <div id="encoreRows" class="setlist-song-rows"></div>
                    <div style="text-align: center; margin-bottom: 24px;">
                        <button type="button" class="mypage-add-button" title="曲を追加" onclick="addSongRow('encoreRows', 'encore')" style="border: none;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ $tourId === 'new' ? route('mypage.attendances.tours', $artistId === 'new' ? ['artistId' => 'new', 'name' => $artistName] : $artistId) : route('mypage.attendances.setlists', $tourId) }}" class="btn btn-outline-secondary w-100">戻る</a>
                        <button type="submit" class="btn btn-outline-dark w-100">次へ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .setlist-song-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .setlist-song-row .song-row-number {
        flex: 0 0 auto;
        width: 1.8em;
        text-align: right;
        color: #999;
        font-size: 0.9em;
    }
    .setlist-song-row input[type="text"] {
        flex: 1;
    }
    .setlist-song-row .remove-row-btn {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 4px 8px;
        font-size: 20px;
    }
    </style>
    <script>
    function addSongRow(containerId, fieldName) {
        const container = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'setlist-song-row';
        row.innerHTML = `
            <span class="song-row-number"></span>
            <input type="text" class="form-control" name="${fieldName}[]" list="songTitleOptions" placeholder="曲名">
            <button type="button" class="remove-row-btn" title="削除" onclick="this.closest('.setlist-song-row').remove(); renumberRows('${containerId}');">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;
        container.appendChild(row);
        renumberRows(containerId);
        row.querySelector('input').focus();
    }

    function renumberRows(containerId) {
        const container = document.getElementById(containerId);
        container.querySelectorAll('.setlist-song-row').forEach((row, index) => {
            row.querySelector('.song-row-number').textContent = (index + 1) + '.';
        });
    }

    // 初期表示時に本編・アンコールそれぞれ1行ずつ用意しておく
    addSongRow('setlistRows', 'setlist');
    addSongRow('encoreRows', 'encore');
    </script>
@endsection
