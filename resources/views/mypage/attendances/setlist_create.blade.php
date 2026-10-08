@extends('layouts.app')
@section('title', 'Yuki Official - セットリストを入力')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
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
            <p class="database-subtitle" style="text-align: center;">セットリストを入力</p>
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
                        <input type="hidden" name="is_fes" value="{{ $isFes ? 1 : 0 }}">
                    @endif

                    <h5 style="font-size: 0.9rem; color: #999; letter-spacing: 1px;">本編</h5>
                    <div id="setlistRows" class="setlist-song-rows"></div>
                    <div style="text-align: center; margin-bottom: 24px;">
                        <button type="button" class="mypage-add-button" title="曲を追加" onclick="addSongRow('setlistRows', 'setlist')" style="border: none;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <h5 style="font-size: 0.9rem; color: #999; letter-spacing: 1px;">ENCORE</h5>
                    <div id="encoreRows" class="setlist-song-rows"></div>
                    <div style="text-align: center; margin-bottom: 24px;">
                        <button type="button" class="mypage-add-button" title="曲を追加" onclick="addSongRow('encoreRows', 'encore')" style="border: none;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-outline-dark w-100">次へ</button>
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
    function addSongRow(containerId, fieldName) {
        const container = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'setlist-song-row';
        row.innerHTML = `
            <span class="manage-drag-handle" title="ドラッグで並べ替え"><i class="fa-solid fa-grip-lines"></i></span>
            <span class="song-row-number"></span>
            <div class="song-row-inputs">
                <input type="text" class="form-control song-row-title" name="${fieldName}[]" list="songTitleOptions" placeholder="曲名">
                <input type="text" class="form-control song-row-alt" name="${fieldName}_alt[]" placeholder="別表記 / カバーなど">
            </div>
            <button type="button" class="remove-row-btn" title="削除" onclick="this.closest('.setlist-song-row').remove(); renumberRows('${containerId}');">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
        container.appendChild(row);
        setupSongRowDrag(row, container);
        renumberRows(containerId);
        row.querySelector('.song-row-title').focus();
    }

    // --- 上下ボタンで1つずつ順位を入れ替える（ドラッグ操作は誤操作が多いため） ---
    function setupSongRowDrag(row, container) {
        // 曲の並べ替え（manage/songs）と同じく、「≡」をつかんでドラッグで並べ替える
        makeSongRowsSortable(container, () => renumberRows(container.id));
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
