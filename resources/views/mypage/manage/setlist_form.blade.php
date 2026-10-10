@extends('layouts.app')

@php
    $pageLabel = $setlist ? 'パターンを編集' : 'パターンを追加';
@endphp

@section('title', $concert->title . ' ' . $pageLabel . ' - Manage My Artists & Setlists')
@section('og_title', $concert->title . ' ' . $pageLabel . ' - Yuki Official')

@section('content')
    {{-- セットリストパターンの追加・編集のページ（パターンごとの個別ページ）。保存するとパターンの一覧に戻る --}}
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists', 'url' => route('mypage.manage.index')],
                ['label' => $artist->name, 'url' => route('mypage.manage.artist', $artist->id)],
                ['label' => 'ツアーを管理', 'url' => route('mypage.manage.concerts', $artist->id)],
                ['label' => $concert->title, 'url' => route('mypage.manage.setlists', [$artist->id, $concert->id])],
                ['label' => $pageLabel],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $artist->name }}</p>
            <h1 class="database-title" style="text-align: center;">{{ $concert->title }}</h1>
            <p class="database-subtitle" style="text-align: center;">{{ $pageLabel }}</p>
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

                <datalist id="songTitleOptions">
                    @foreach ($songTitles as $title)
                        <option value="{{ $title }}"></option>
                    @endforeach
                </datalist>

                <form method="POST" action="{{ $setlist ? route('mypage.manage.setlists.update', [$artist->id, $concert->id, $setlist->id]) : route('mypage.manage.setlists.store', [$artist->id, $concert->id]) }}">
                    @csrf
                    <div class="mb-4">
                        <input type="text" class="form-control" name="subtitle" placeholder="パターン名を入力（任意）" value="{{ old('subtitle', $setlist?->subtitle) }}">
                    </div>

                    @foreach (['setlist' => ['本編', $setlistRows], 'encore' => ['ENCORE', $encoreRows]] as $field => [$heading, $rows])
                        <h5 style="font-size: 0.9rem; color: #999; letter-spacing: 1px;">{{ $heading }}</h5>
                        <div class="setlist-song-rows" data-field="{{ $field }}">
                            @foreach ($rows as $inputRow)
                                <div class="setlist-song-row">
                                    <span class="manage-drag-handle" title="ドラッグで並べ替え"><i class="fa-solid fa-grip-lines"></i></span>
                                    <span class="song-row-number"></span>
                                    <div class="song-row-inputs">
                                        <input type="text" class="form-control song-row-title" name="{{ $field }}[]" list="songTitleOptions" placeholder="曲名" value="{{ $inputRow['title'] }}">
                                        <input type="text" class="form-control song-row-alt" name="{{ $field }}_alt[]" placeholder="別表記 / カバーなど" value="{{ $inputRow['alternative_title'] }}">
                                    </div>
                                    <button type="button" class="remove-row-btn" title="削除" onclick="const c = this.closest('.setlist-song-rows'); this.closest('.setlist-song-row').remove(); renumberRows(c);">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <div class="mypage-add-row" style="margin-bottom: 24px;">
                            <span class="mypage-add-row-label">曲を追加</span>
                            <button type="button" class="mypage-add-button" title="曲を追加" style="border: none;" onclick="addSetlistSongRow(this, '{{ $field }}')">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    @endforeach

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-dark w-100">保存</button>
                        @if ($setlist)
                            <button type="button" class="btn btn-outline-danger w-100" onclick="if (confirm('このセットリストパターンを削除しますか？')) { document.getElementById('setlistDeleteForm').submit(); }">削除</button>
                        @endif
                    </div>
                    {{-- 保存せずにパターンの一覧へ戻る --}}
                    <a href="{{ route('mypage.manage.setlists', [$artist->id, $concert->id]) }}" class="btn btn-outline-secondary w-100" style="margin-top: 8px;">戻る</a>
                </form>
                @if ($setlist)
                    <form method="POST" action="{{ route('mypage.manage.setlists.destroy', [$artist->id, $concert->id, $setlist->id]) }}" id="setlistDeleteForm" hidden>
                        @csrf
                    </form>
                @endif
            </div>
        </div>
    </div>

    <style>
    .setlist-song-row {
        position: relative;
        border-radius: 10px;
        margin-bottom: 10px;
        background: #f8f8f8;
        border: 1px solid #eee;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .setlist-song-row .song-row-number {
        flex: 0 0 auto;
        width: 1.4em;
        text-align: right;
        color: #999;
        font-size: 0.85em;
    }
    .setlist-song-row input[type="text"] {
        flex: 1;
        min-width: 0;
        border: none;
        padding: 4px 0;
        background: transparent;
        font-size: 0.9em;
    }
    .setlist-song-row input[type="text"]:focus {
        outline: none;
        box-shadow: none;
    }
    /* 曲名の下に別表記の欄（アルバムの登録画面と同じ）。曲名が空で別表記だけなら、カバーなどとして保存する */
    .setlist-song-row .song-row-inputs {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    .setlist-song-row .song-row-inputs input::placeholder {
        color: #c4c4c4;
        opacity: 1;
    }
    .setlist-song-row .song-row-alt {
        font-size: 0.8em !important;
        color: #888;
        padding-top: 0 !important;
    }
    .setlist-song-row .remove-row-btn {
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        padding: 4px 8px;
        flex-shrink: 0;
    }
    </style>

    @include('mypage.manage._song_rows_sortable')
    <script>
    function addSetlistSongRow(button, fieldName) {
        const container = button.closest('div').previousElementSibling;
        const row = document.createElement('div');
        row.className = 'setlist-song-row';
        row.innerHTML = `
            <span class="manage-drag-handle" title="ドラッグで並べ替え"><i class="fa-solid fa-grip-lines"></i></span>
            <span class="song-row-number"></span>
            <div class="song-row-inputs">
                <input type="text" class="form-control song-row-title" name="${fieldName}[]" list="songTitleOptions" placeholder="曲名">
                <input type="text" class="form-control song-row-alt" name="${fieldName}_alt[]" placeholder="別表記 / カバーなど">
            </div>
            <button type="button" class="remove-row-btn" title="削除" onclick="const c = this.closest('.setlist-song-rows'); this.closest('.setlist-song-row').remove(); renumberRows(c);">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
        container.appendChild(row);
        renumberRows(container);
        row.querySelector('.song-row-title').focus();
    }

    function renumberRows(container) {
        container.querySelectorAll('.setlist-song-row').forEach((row, index) => {
            row.querySelector('.song-row-number').textContent = (index + 1) + '.';
        });
    }

    // 曲の並べ替え（manage/songs）と同じく、「≡」をつかんでドラッグで並べ替える
    document.querySelectorAll('.setlist-song-rows').forEach((container) => {
        makeSongRowsSortable(container, () => renumberRows(container));
        renumberRows(container);
    });
    </script>
@endsection
