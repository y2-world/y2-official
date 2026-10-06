@extends('layouts.app')

@php
    $kindLabel = $kind === 'album' ? 'アルバム' : 'シングル';
    // 入力エラーで戻ったときは、入力していた曲名を出し直す
    $initialTracks = old('tracks', $discTracks);
@endphp

@section('title', $artist->name . ' ' . $kindLabel . ($disc ? 'を編集' : 'を追加') . ' - Manage My Artists & Setlists')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists', 'url' => route('mypage.manage.index')],
                ['label' => $artist->name, 'url' => route('mypage.manage.artist', $artist->id)],
                ['label' => 'シングル・アルバムを管理', 'url' => route('mypage.manage.discs', $artist->id)],
                ['label' => $disc ? $disc->title : $kindLabel . 'を追加'],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $artist->name }}</p>
            <h1 class="database-title" style="text-align: center;">{{ $kindLabel }}{{ $disc ? 'を編集' : 'を追加' }}</h1>
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

                <form method="POST" action="{{ $disc ? route('mypage.manage.discs.update', [$artist->id, $kind, $disc->id]) : route('mypage.manage.discs.store', $artist->id) }}">
                    @csrf
                    <input type="hidden" name="kind" value="{{ $kind }}">

                    <div class="mb-3">
                        <label for="disc_title" class="form-label">タイトル</label>
                        <input type="text" class="form-control" id="disc_title" name="title" value="{{ old('title', $disc?->title) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="disc_date" class="form-label">発売日</label>
                        <input type="date" class="form-control" id="disc_date" name="date" value="{{ old('date', $disc?->date?->format('Y-m-d')) }}">
                    </div>
                    {{-- シングルは EP、アルバムはミニアルバム・ベスト（公式と同じ） --}}
                    <div class="mb-4" style="display: flex; gap: 20px; flex-wrap: wrap;">
                        @if ($kind === 'single')
                            <label style="display: inline-flex; align-items: center; gap: 6px;">
                                <input type="checkbox" name="ep" value="1" @checked(old('ep', $disc?->ep))> EP
                            </label>
                        @else
                            <label style="display: inline-flex; align-items: center; gap: 6px;">
                                <input type="checkbox" name="mini" value="1" @checked(old('mini', $disc?->mini))> ミニアルバム
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px;">
                                <input type="checkbox" name="best" value="1" @checked(old('best', $disc?->best))> ベストアルバム
                            </label>
                        @endif
                    </div>

                    <h5 style="font-size: 0.9rem; color: #999; letter-spacing: 1px;">収録曲</h5>
                    <div id="discSections"></div>
                    @if ($kind === 'album')
                        <div style="text-align: center; margin-bottom: 24px;">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDiscSection([])">ディスクを追加</button>
                        </div>
                    @endif

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-outline-dark w-100">{{ $disc ? '更新' : '登録' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .setlist-song-row {
        position: relative;
        border-radius: 10px;
        margin-bottom: 10px;
        background: white;
        border: 1px solid #eee;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .setlist-song-row .song-row-reorder-buttons {
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }
    .setlist-song-row .song-row-reorder-up,
    .setlist-song-row .song-row-reorder-down {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 2px 6px;
        line-height: 1;
    }
    .setlist-song-row .song-row-reorder-up:hover,
    .setlist-song-row .song-row-reorder-down:hover {
        color: #667eea;
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
        font-size: 0.9em;
    }
    .setlist-song-row input[type="text"]:focus {
        outline: none;
        box-shadow: none;
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
    <script>
    const isAlbum = @json($kind === 'album');
    let discCount = 0;

    // ディスク（アルバムは Disc 1, Disc 2 …、シングルは1つだけ）ごとに曲の行を並べる
    function addDiscSection(titles) {
        discCount++;
        const index = discCount - 1;
        const section = document.createElement('div');
        section.className = 'disc-section';
        const containerId = 'discRows' + index;
        section.innerHTML = `
            ${isAlbum ? `<h6 class="disc-section-title" style="font-size: 0.85rem; color: #999; margin: 12px 0 8px;">Disc ${discCount}</h6>` : ''}
            <div id="${containerId}" class="setlist-song-rows" data-disc-index="${index}"></div>
            <div style="text-align: center; margin-bottom: 24px;">
                <button type="button" class="mypage-add-button" title="曲を追加" style="border: none;">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        `;
        document.getElementById('discSections').appendChild(section);
        section.querySelector('.mypage-add-button').addEventListener('click', () => addSongRow(containerId, ''));
        (titles.length ? titles : ['']).forEach((title) => addSongRow(containerId, title, true));
    }

    function addSongRow(containerId, value, keepFocus) {
        const container = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'setlist-song-row';
        row.innerHTML = `
            <div class="song-row-reorder-buttons">
                <button type="button" class="song-row-reorder-up" title="上へ"><i class="fa-solid fa-chevron-up"></i></button>
                <button type="button" class="song-row-reorder-down" title="下へ"><i class="fa-solid fa-chevron-down"></i></button>
            </div>
            <span class="song-row-number"></span>
            <input type="text" class="form-control" name="tracks[${container.dataset.discIndex}][]" list="songTitleOptions" placeholder="曲名">
            <button type="button" class="remove-row-btn" title="削除"><i class="fa-solid fa-trash"></i></button>
        `;
        row.querySelector('input').value = value || '';
        row.querySelector('.remove-row-btn').addEventListener('click', () => { row.remove(); renumberRows(containerId); });
        row.querySelector('.song-row-reorder-up').addEventListener('click', () => {
            const prev = row.previousElementSibling;
            if (!prev) return;
            container.insertBefore(row, prev);
            renumberRows(containerId);
        });
        row.querySelector('.song-row-reorder-down').addEventListener('click', () => {
            const next = row.nextElementSibling;
            if (!next) return;
            container.insertBefore(next, row);
            renumberRows(containerId);
        });
        container.appendChild(row);
        renumberRows(containerId);
        if (!keepFocus) row.querySelector('input').focus();
    }

    function renumberRows(containerId) {
        document.getElementById(containerId).querySelectorAll('.setlist-song-row').forEach((row, index) => {
            row.querySelector('.song-row-number').textContent = (index + 1) + '.';
        });
    }

    // 登録済みの収録曲（新しく作るときは空の1行）を出す
    @json(array_values($initialTracks)).forEach((titles) => addDiscSection(Object.values(titles || {}).filter((t) => t !== null)));
    </script>
@endsection
