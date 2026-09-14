@extends('layouts.app')

@section('title', $concert->title . ' セットリストを管理 - Manage My Artists & Setlists')
@section('og_title', $concert->title . ' セットリストを管理 - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists', 'url' => route('mypage.manage.index')],
                ['label' => $artist->name, 'url' => route('mypage.manage.artist', $artist->id)],
                ['label' => 'ツアーを管理', 'url' => route('mypage.manage.concerts', $artist->id)],
                ['label' => $concert->title],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $artist->name }}</p>
            <h1 class="database-title" style="text-align: center;">
                {{ $concert->title }}
                <button type="button" class="manage-edit-btn" id="concertInfoEditToggle" title="編集" style="color: rgba(255, 255, 255, 0.8); vertical-align: middle; font-size: 0.5em;">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </h1>
            <p class="database-subtitle" style="text-align: center;">
                @if ($concert->date1)
                    {{ \Carbon\Carbon::parse($concert->date1)->format('Y.m.d') }}
                    @if ($concert->date2 && $concert->date2 !== $concert->date1)
                        - {{ \Carbon\Carbon::parse($concert->date2)->format('Y.m.d') }}
                    @endif
                @else
                    開催期間未設定
                @endif
            </p>
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


                <form method="POST" action="{{ route('mypage.manage.concerts.update', [$artist->id, $concert->id]) }}" id="concertInfoForm" hidden style="margin-bottom: 24px;">
                    @csrf
                    <div class="mb-3">
                        <label for="concert_title" class="form-label">ツアー名</label>
                        <input type="text" class="form-control" id="concert_title" name="title" value="{{ $concert->title }}" required>
                    </div>
                    <div class="mb-3 d-flex gap-2">
                        <div style="flex: 1;">
                            <label for="concert_date1" class="form-label">開始日</label>
                            <input type="date" class="form-control" id="concert_date1" name="date1" value="{{ $concert->date1 }}">
                        </div>
                        <div style="flex: 1;">
                            <label for="concert_date2" class="form-label">終了日</label>
                            <input type="date" class="form-control" id="concert_date2" name="date2" value="{{ $concert->date2 }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">保存</button>
                </form>

                @foreach ($setlists as $setlist)
                    <div class="manage-row" style="display: block;" data-setlist-id="{{ $setlist->id }}">
                        <div class="setlist-pattern-summary">
                            <span class="select-card-icon"><i class="fa-solid fa-music"></i></span>
                            <span class="manage-row-title">
                                @php
                                    $subtitleRendered = renderSubtitleWithGreyedVenues($setlist->subtitle);
                                    $subtitleHtml = implode('<br>', $subtitleRendered['lines']);
                                @endphp
                                <span class="setlist-pattern-title-display" data-title="{{ $setlist->subtitle }}" @if (!$setlist->subtitle) hidden @endif @if ($subtitleRendered['font_size']) style="font-size: {{ $subtitleRendered['font_size'] }};" @endif>{!! $subtitleHtml !!}</span>
                                @php
                                    $songCount = count($setlist->setlist ?? []) + count($setlist->encore ?? []);
                                @endphp
                                <span class="setlist-pattern-song-count" style="color: #999; font-size: 0.85rem; display: block;">{{ $songCount }}曲</span>
                            </span>
                            <button type="button" class="manage-edit-btn setlist-pattern-edit-toggle" title="編集">
                                <i class="fa-solid fa-pen"></i>
                                <i class="fa-solid fa-check" hidden></i>
                            </button>
                            <button type="button" class="manage-delete-btn setlist-pattern-delete" data-delete-url="{{ route('mypage.manage.setlists.destroy', [$artist->id, $concert->id, $setlist->id]) }}" title="削除">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('mypage.manage.setlists.update', [$artist->id, $concert->id, $setlist->id]) }}" class="setlist-pattern-form" hidden style="margin-top: 16px;">
                            @csrf
                            <div class="mb-3">
                                <input type="text" class="form-control setlist-pattern-title-input" name="subtitle" placeholder="パターン名を入力" value="{{ $setlist->subtitle }}">
                            </div>
                            <div class="setlist-song-rows" data-field="setlist">
                                @foreach ($setlist->setlist ?? [] as $item)
                                    <div class="setlist-song-row">
                                        <div class="song-row-reorder-buttons">
                                            <button type="button" class="song-row-reorder-up" title="上へ"><i class="fa-solid fa-chevron-up"></i></button>
                                            <button type="button" class="song-row-reorder-down" title="下へ"><i class="fa-solid fa-chevron-down"></i></button>
                                        </div>
                                        <span class="song-row-number"></span>
                                        <input type="text" class="form-control song-title-input" name="setlist[]" placeholder="曲名" value="{{ $songTitles[$item['song']] ?? '' }}">
                                        <button type="button" class="remove-row-btn" title="削除" onclick="this.closest('.setlist-song-row').remove(); renumberRows(this.closest('.setlist-song-rows'));">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <div style="text-align: center; margin-bottom: 16px;">
                                <button type="button" class="mypage-add-button" title="曲を追加" style="border: none;" onclick="addSetlistSongRow(this, 'setlist')">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>

                            <h5 style="font-size: 0.9rem; color: #999; letter-spacing: 1px;">ENCORE</h5>
                            <div class="setlist-song-rows" data-field="encore">
                                @foreach ($setlist->encore ?? [] as $item)
                                    <div class="setlist-song-row">
                                        <div class="song-row-reorder-buttons">
                                            <button type="button" class="song-row-reorder-up" title="上へ"><i class="fa-solid fa-chevron-up"></i></button>
                                            <button type="button" class="song-row-reorder-down" title="下へ"><i class="fa-solid fa-chevron-down"></i></button>
                                        </div>
                                        <span class="song-row-number"></span>
                                        <input type="text" class="form-control song-title-input" name="encore[]" placeholder="曲名" value="{{ $songTitles[$item['song']] ?? '' }}">
                                        <button type="button" class="remove-row-btn" title="削除" onclick="this.closest('.setlist-song-row').remove(); renumberRows(this.closest('.setlist-song-rows'));">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <div style="text-align: center; margin-bottom: 16px;">
                                <button type="button" class="mypage-add-button" title="曲を追加" style="border: none;" onclick="addSetlistSongRow(this, 'encore')">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>

                            <button type="submit" class="btn btn-outline-dark w-100">保存</button>
                        </form>
                    </div>
                @endforeach

                <p id="noSetlistsMessage" style="text-align: center; color: #999;" @if ($setlists->isNotEmpty()) hidden @endif>まだセットリストパターンがありません。</p>
            </div>
        </div>
    </div>

    <style>
    .setlist-pattern-summary {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .setlist-song-row {
        position: relative;
        border-radius: 10px;
        margin-bottom: 10px;
        background: #f8f8f8;
        border: 1px solid #eee;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
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
        width: 1.6em;
        text-align: right;
        color: #999;
        font-size: 0.9em;
    }
    .setlist-song-row input[type="text"] {
        flex: 1;
        border: none;
        padding: 4px 0;
        background: transparent;
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
    const songTitleOptions = @json($songTitles->values());

    function addSetlistSongRow(button, fieldName) {
        const container = button.closest('div').previousElementSibling;
        const row = document.createElement('div');
        row.className = 'setlist-song-row';
        row.innerHTML = `
            <div class="song-row-reorder-buttons">
                <button type="button" class="song-row-reorder-up" title="上へ"><i class="fa-solid fa-chevron-up"></i></button>
                <button type="button" class="song-row-reorder-down" title="下へ"><i class="fa-solid fa-chevron-down"></i></button>
            </div>
            <span class="song-row-number"></span>
            <input type="text" class="form-control song-title-input" name="${fieldName}[]" placeholder="曲名">
            <button type="button" class="remove-row-btn" title="削除" onclick="this.closest('.setlist-song-row').remove(); renumberRows(this.closest('.setlist-song-rows'));">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
        container.appendChild(row);
        setupSongRowDrag(row, container);
        renumberRows(container);
        const input = row.querySelector('input');
        input.dataset.autocompleteOptions = JSON.stringify(songTitleOptions);
        initAutocomplete(input);
        input.focus();
    }

    // --- 上下ボタンで1つずつ順位を入れ替える（ドラッグ操作は誤操作が多いため） ---
    function setupSongRowDrag(row, container) {
        row.querySelector('.song-row-reorder-up').addEventListener('click', () => {
            const prev = row.previousElementSibling;
            if (!prev) return;
            container.insertBefore(row, prev);
            renumberRows(container);
        });
        row.querySelector('.song-row-reorder-down').addEventListener('click', () => {
            const next = row.nextElementSibling;
            if (!next) return;
            container.insertBefore(next, row);
            renumberRows(container);
        });
    }

    function renumberRows(container) {
        container.querySelectorAll('.setlist-song-row').forEach((row, index) => {
            row.querySelector('.song-row-number').textContent = (index + 1) + '.';
        });
    }

    document.querySelectorAll('.setlist-song-rows').forEach((container) => {
        container.querySelectorAll('.setlist-song-row').forEach((row) => setupSongRowDrag(row, container));
        renumberRows(container);
    });

    // initAutocompleteはlayouts/app.blade.php側の<script>で定義されるが、
    // そちらは@yield('content')より後にレンダリングされるため、ここでの即時実行では
    // 未定義エラーになる。DOMContentLoadedまで遅らせて確実に定義済みの状態で呼び出す。
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.song-title-input').forEach((input) => {
            input.dataset.autocompleteOptions = JSON.stringify(songTitleOptions);
            initAutocomplete(input);
        });
    });

    document.getElementById('concertInfoEditToggle').addEventListener('click', () => {
        const form = document.getElementById('concertInfoForm');
        form.hidden = !form.hidden;
        if (!form.hidden) {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    // 編集アイコンをクリックすると、パターン名の見出し表示を隠し、
    // 曲目・パターン名をまとめて編集できるフォームを表示する（もう一度押すと閉じる）。
    document.querySelectorAll('.setlist-pattern-edit-toggle').forEach((btn) => {
        const row = btn.closest('.manage-row');
        const titleDisplay = row.querySelector('.setlist-pattern-title-display');
        const songCount = row.querySelector('.setlist-pattern-song-count');
        const form = row.querySelector('.setlist-pattern-form');
        const penIcon = btn.querySelector('.fa-pen');
        const checkIcon = btn.querySelector('.fa-check');

        btn.addEventListener('click', () => {
            const willEdit = form.hidden;
            form.hidden = !willEdit;
            titleDisplay.hidden = willEdit ? true : !titleDisplay.dataset.title;
            songCount.hidden = willEdit;
            penIcon.hidden = willEdit;
            checkIcon.hidden = !willEdit;
        });
    });

    document.querySelectorAll('.setlist-pattern-delete').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!confirm('このセットリストパターンを削除しますか？')) return;
            const row = btn.closest('.manage-row');
            fetch(btn.dataset.deleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
                .then((res) => res.json())
                .then((data) => {
                    row.remove();
                    showAppToast(data.message);
                    if (!document.querySelector('[data-setlist-id]')) {
                        document.getElementById('noSetlistsMessage').hidden = false;
                    }
                });
        });
    });
    </script>
@endsection
