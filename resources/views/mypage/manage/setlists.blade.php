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

                <datalist id="songTitleOptions">
                    @foreach ($songTitles as $title)
                        <option value="{{ $title }}"></option>
                    @endforeach
                </datalist>

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

                <div id="setlistList">
                @foreach ($setlists as $index => $setlist)
                    <div class="manage-row" style="display: block;" data-setlist-id="{{ $setlist->id }}">
                        <div class="setlist-pattern-summary">
                            <div class="manage-reorder-buttons">
                                <button type="button" class="manage-reorder-up setlist-pattern-reorder-up" title="上へ"><i class="fa-solid fa-chevron-up"></i></button>
                                <button type="button" class="manage-reorder-down setlist-pattern-reorder-down" title="下へ"><i class="fa-solid fa-chevron-down"></i></button>
                            </div>
                            <span class="select-card-icon"><i class="fa-solid fa-music"></i></span>
                            <span class="manage-row-title">
                                @php
                                    $subtitleRendered = renderSubtitleWithGreyedVenues($setlist->subtitle);
                                    $subtitleHtml = implode('<br>', $subtitleRendered['lines']);
                                    // パターンが複数あるのにタイトルが無いと見分けがつかないため、
                                    // タイトル未設定のパターンには「パターンN」を自動で補う（1件のみなら不要）。
                                    $fallbackPatternLabel = (!$setlist->subtitle && $setlists->count() > 1)
                                        ? 'パターン' . ($index + 1)
                                        : null;
                                @endphp
                                @if ($fallbackPatternLabel)
                                    <span class="setlist-pattern-title-fallback" style="color: #999;">{{ $fallbackPatternLabel }}</span>
                                @endif
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
                            <button type="button" class="manage-edit-btn setlist-pattern-duplicate" data-duplicate-url="{{ route('mypage.manage.setlists.duplicate', [$artist->id, $concert->id, $setlist->id]) }}" title="複製">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                            <button type="button" class="manage-delete-btn setlist-pattern-delete" data-delete-url="{{ route('mypage.manage.setlists.destroy', [$artist->id, $concert->id, $setlist->id]) }}" title="削除">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('mypage.manage.setlists.update', [$artist->id, $concert->id, $setlist->id]) }}" class="setlist-pattern-form" hidden style="margin-top: 16px;">
                            @csrf
                            <div class="mb-3">
                                <input type="text" class="form-control setlist-pattern-title-input" name="subtitle" placeholder="パターン名を入力（任意）" value="{{ $setlist->subtitle }}">
                            </div>
                            <div class="setlist-song-rows" data-field="setlist">
                                @foreach ($setlist->setlist ?? [] as $item)
                                    <div class="setlist-song-row">
                                        <div class="song-row-reorder-buttons">
                                            <button type="button" class="song-row-reorder-up" title="上へ"><i class="fa-solid fa-chevron-up"></i></button>
                                            <button type="button" class="song-row-reorder-down" title="下へ"><i class="fa-solid fa-chevron-down"></i></button>
                                        </div>
                                        <span class="song-row-number"></span>
                                        <input type="text" class="form-control" name="setlist[]" list="songTitleOptions" placeholder="曲名" value="{{ $songTitles[$item['song']] ?? '' }}">
                                        <button type="button" class="remove-row-btn" title="削除" onclick="const c = this.closest('.setlist-song-rows'); this.closest('.setlist-song-row').remove(); renumberRows(c);">
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
                                        <input type="text" class="form-control" name="encore[]" list="songTitleOptions" placeholder="曲名" value="{{ $songTitles[$item['song']] ?? '' }}">
                                        <button type="button" class="remove-row-btn" title="削除" onclick="const c = this.closest('.setlist-song-rows'); this.closest('.setlist-song-row').remove(); renumberRows(c);">
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
                </div>

                <p id="noSetlistsMessage" style="text-align: center; color: #999; margin-top: 0;" @if ($setlists->isNotEmpty()) hidden @endif>まだセットリストパターンがありません。</p>

                <div style="margin-top: 24px; text-align: center;">
                    <button type="button" id="newSetlistPatternBtn" class="mypage-add-button" title="セットリストパターンを追加" style="border: none;">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                {{-- 新規パターン用フォームの雛形。「＋」を押した時にこれを複製して表示する。
                     押しただけではDBに何も作らず、この中の「保存」を押した時点で初めてstoreSetlistへ送信する。 --}}
                <template id="newSetlistPatternTemplate">
                    <div class="manage-row" style="display: block;">
                        <form method="POST" action="{{ route('mypage.manage.setlists.store', [$artist->id, $concert->id]) }}" class="setlist-pattern-form" style="margin-top: 16px;">
                            @csrf
                            <div class="mb-3">
                                <input type="text" class="form-control setlist-pattern-title-input" name="subtitle" placeholder="パターン名を入力（任意）">
                            </div>
                            <div class="setlist-song-rows" data-field="setlist"></div>
                            <div style="text-align: center; margin-bottom: 16px;">
                                <button type="button" class="mypage-add-button" title="曲を追加" style="border: none;" onclick="addSetlistSongRow(this, 'setlist')">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>

                            <h5 style="font-size: 0.9rem; color: #999; letter-spacing: 1px;">ENCORE</h5>
                            <div class="setlist-song-rows" data-field="encore"></div>
                            <div style="text-align: center; margin-bottom: 16px;">
                                <button type="button" class="mypage-add-button" title="曲を追加" style="border: none;" onclick="addSetlistSongRow(this, 'encore')">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary w-100 new-setlist-pattern-cancel">キャンセル</button>
                                <button type="submit" class="btn btn-outline-dark w-100">保存</button>
                            </div>
                        </form>
                    </div>
                </template>
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
            <input type="text" class="form-control" name="${fieldName}[]" list="songTitleOptions" placeholder="曲名">
            <button type="button" class="remove-row-btn" title="削除" onclick="const c = this.closest('.setlist-song-rows'); this.closest('.setlist-song-row').remove(); renumberRows(c);">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
        container.appendChild(row);
        setupSongRowDrag(row, container);
        renumberRows(container);
        row.querySelector('input').focus();
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
        const titleFallback = row.querySelector('.setlist-pattern-title-fallback');
        const songCount = row.querySelector('.setlist-pattern-song-count');
        const form = row.querySelector('.setlist-pattern-form');
        const penIcon = btn.querySelector('.fa-pen');
        const checkIcon = btn.querySelector('.fa-check');

        btn.addEventListener('click', () => {
            const willEdit = form.hidden;
            form.hidden = !willEdit;
            titleDisplay.hidden = willEdit ? true : !titleDisplay.dataset.title;
            if (titleFallback) {
                titleFallback.hidden = willEdit;
            }
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

    // パターンの複製：サーバー側で曲目・パターン名をそのままコピーした新規パターンを作成し、
    // 一覧を再読み込みして末尾に追加されたそのカードを開いた状態で表示する。
    document.querySelectorAll('.setlist-pattern-duplicate').forEach((btn) => {
        btn.addEventListener('click', () => {
            fetch(btn.dataset.duplicateUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
                .then((res) => res.json())
                .then((data) => {
                    window.location.href = data.redirect;
                });
        });
    });

    // パターン自体（セットリストパターンの表示順）を上下ボタンで1つずつ入れ替える。
    // 曲行の並べ替えと同じ考え方（ドラッグは誤操作が多いため上下ボタン方式）。
    function persistSetlistOrder() {
        const setlistList = document.getElementById('setlistList');
        const setlistIds = Array.from(setlistList.querySelectorAll('[data-setlist-id]')).map((row) => row.dataset.setlistId);
        fetch('{{ route('mypage.manage.setlists.reorder', [$artist->id, $concert->id]) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ setlist_ids: setlistIds }),
        });
    }

    document.querySelectorAll('.setlist-pattern-reorder-up').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('[data-setlist-id]');
            const prev = row.previousElementSibling;
            if (!prev || !prev.dataset.setlistId) return;
            row.parentElement.insertBefore(row, prev);
            persistSetlistOrder();
        });
    });
    document.querySelectorAll('.setlist-pattern-reorder-down').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('[data-setlist-id]');
            const next = row.nextElementSibling;
            if (!next || !next.dataset.setlistId) return;
            row.parentElement.insertBefore(next, row);
            persistSetlistOrder();
        });
    });

    // 「＋」を押しても何も保存せず、曲目編集フォーム（template）を複製して開いた状態で挿入するだけ。
    // この中の「保存」を押した時点で初めてstoreSetlistへ送信され、DBに書き込まれる。
    // 「キャンセル」または他のパターン追加を押した場合は、DOMから取り除くだけで何も残らない。
    document.getElementById('newSetlistPatternBtn').addEventListener('click', () => {
        // 既に開いている未保存フォームがあれば、二重に増やさず先に片付ける
        document.querySelector('.new-setlist-pattern-row')?.remove();

        const template = document.getElementById('newSetlistPatternTemplate');
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.manage-row');
        row.classList.add('new-setlist-pattern-row');

        const setlistList = document.getElementById('setlistList');
        setlistList.appendChild(fragment);
        document.getElementById('noSetlistsMessage').hidden = true;

        row.querySelector('.new-setlist-pattern-cancel').addEventListener('click', () => {
            row.remove();
            if (!document.querySelector('[data-setlist-id]')) {
                document.getElementById('noSetlistsMessage').hidden = false;
            }
        });
        row.querySelector('.setlist-pattern-title-input').focus();
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    // 複製直後（?open=<setlistId>）は、そのカードの編集フォームを開いた状態で表示する
    (function () {
        const params = new URLSearchParams(window.location.search);
        const openId = params.get('open');
        if (!openId) return;

        const row = document.querySelector(`[data-setlist-id="${openId}"]`);
        const editBtn = row?.querySelector('.setlist-pattern-edit-toggle');
        editBtn?.click();
        row?.scrollIntoView({ behavior: 'smooth', block: 'center' });

        const url = new URL(window.location.href);
        url.searchParams.delete('open');
        window.history.replaceState({}, '', url);
    })();
    </script>
@endsection
