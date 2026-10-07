@extends('layouts.app')

@section('title', $artist->name . ' 曲を管理 - Manage My Artists & Setlists')
@section('og_title', $artist->name . ' 曲を管理 - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists', 'url' => route('mypage.manage.index')],
                ['label' => $artist->name, 'url' => route('mypage.manage.artist', $artist->id)],
                ['label' => '曲を管理'],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $artist->name }}</p>
            <h1 class="database-title" style="text-align: center;">曲を管理</h1>
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

                <form method="POST" action="{{ route('mypage.manage.songs.store', $artist->id) }}" style="margin-bottom: 24px; display: flex; gap: 8px;">
                    @csrf
                    <input type="text" name="title" class="form-control" placeholder="新しい曲名" required>
                    <button type="submit" class="mypage-add-button" title="曲を追加" style="border: none; flex-shrink: 0;">
                        <i class="fas fa-plus"></i>
                    </button>
                </form>

                <div id="songList">
                    @foreach ($songs as $song)
                        <div class="manage-row" data-song-id="{{ $song->id }}">
                            {{-- ここをつかんでドラッグで並べ替える（スマホは少し長押ししてから） --}}
                            <span class="manage-drag-handle" title="ドラッグで並べ替え"><i class="fa-solid fa-grip-lines"></i></span>
                            <span class="manage-row-title" data-title="{{ $song->title }}">{{ $song->title }}</span>
                            <input type="text" class="manage-row-title-input" value="{{ $song->title }}" hidden>
                            <button type="button" class="manage-edit-btn" data-update-url="{{ route('mypage.manage.songs.update', [$artist->id, $song->id]) }}" title="編集">
                                <i class="fa-solid fa-pen"></i>
                                <i class="fa-solid fa-check" hidden></i>
                            </button>
                            <button type="button" class="manage-delete-btn" data-delete-url="{{ route('mypage.manage.songs.destroy', [$artist->id, $song->id]) }}" title="削除">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
                <p id="noSongsMessage" style="text-align: center; color: #999; margin-top: 0;" @if ($songs->isNotEmpty()) hidden @endif>まだ曲がありません。</p>
            </div>
        </div>
    </div>

    <style>
    .manage-drag-handle {
        flex-shrink: 0;
        color: #bbb;
        cursor: grab;
        padding: 6px 8px 6px 2px;
        touch-action: none;
    }
    .manage-drag-handle:active { cursor: grabbing; }
    .manage-row.is-drag-ghost { opacity: 0.4; }
    .manage-row.is-drag-chosen { box-shadow: 0 6px 18px rgba(102, 126, 234, 0.3); }
    /* 指（マウス）について動くカード */
    /* ドラッグ中は曲名の文字を選択しない */
    #songList.is-dragging, #songList.is-dragging * { user-select: none; -webkit-user-select: none; }
    .manage-row.is-drag-fallback { opacity: 0.95 !important; box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18); transition: none !important; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <script>
    (function () {
        const songList = document.getElementById('songList');
        if (!songList) return;

        // --- ドラッグで並べ替え。誤操作を防ぐため「≡」をつかんだときだけ動かす（スマホは少し長押ししてから）。
        //     画面の端まで持っていくと自動でスクロールする。離したら並びを保存する ---
        if (window.Sortable) {
            Sortable.create(songList, {
                handle: '.manage-drag-handle',
                draggable: '.manage-row',
                animation: 150,
                delay: 150,
                delayOnTouchOnly: true,
                ghostClass: 'is-drag-ghost',
                chosenClass: 'is-drag-chosen',
                // ブラウザ任せのドラッグだと、つかんだカードが指（マウス）について動かないことがあるので、
                // カードを複製して追従させる方式にする
                forceFallback: true,
                fallbackOnBody: false,
                fallbackClass: 'is-drag-fallback',
                fallbackTolerance: 3,
                scroll: true,
                forceAutoScrollFallback: true,
                scrollSensitivity: 80,
                scrollSpeed: 12,
                onStart: () => songList.classList.add('is-dragging'),
                onEnd: (e) => {
                    songList.classList.remove('is-dragging');
                    if (e.oldIndex !== e.newIndex) persistSongOrder();
                },
            });
        }

        function requestDelete(row) {
            if (!confirm('この曲を削除しますか？')) return;
            fetch(row.querySelector('.manage-delete-btn').dataset.deleteUrl, {
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
                    if (!songList.querySelector('.manage-row')) {
                        document.getElementById('noSongsMessage').hidden = false;
                    }
                });
        }

        // --- 曲名のインライン編集：編集アイコンをクリックでinputに切り替え、
        //     アイコンはペン→チェックに変わる。もう一度クリックすると確定する。
        function setupInlineEdit(row) {
            const titleSpan = row.querySelector('.manage-row-title');
            const titleInput = row.querySelector('.manage-row-title-input');
            const editBtn = row.querySelector('.manage-edit-btn');
            const penIcon = editBtn.querySelector('.fa-pen');
            const checkIcon = editBtn.querySelector('.fa-check');
            const updateUrl = editBtn.dataset.updateUrl;
            let isEditing = false;

            const startEdit = () => {
                isEditing = true;
                titleSpan.hidden = true;
                titleInput.hidden = false;
                penIcon.hidden = true;
                checkIcon.hidden = false;
                titleInput.value = titleSpan.dataset.title;
                titleInput.focus();
                titleInput.setSelectionRange(titleInput.value.length, titleInput.value.length);
            };

            const commitEdit = () => {
                isEditing = false;
                const newTitle = titleInput.value.trim();
                titleInput.hidden = true;
                titleSpan.hidden = false;
                penIcon.hidden = false;
                checkIcon.hidden = true;

                if (newTitle === '' || newTitle === titleSpan.dataset.title) return;

                fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ title: newTitle }),
                })
                    .then((res) => res.json())
                    .then((data) => {
                        titleSpan.textContent = data.title;
                        titleSpan.dataset.title = data.title;
                        showAppToast(data.message);
                    });
            };

            editBtn.addEventListener('click', () => {
                if (isEditing) {
                    commitEdit();
                } else {
                    startEdit();
                }
            });
            titleInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); commitEdit(); }
                if (e.key === 'Escape') { titleInput.value = titleSpan.dataset.title; commitEdit(); }
            });
        }

        function persistSongOrder() {
            const songIds = Array.from(songList.querySelectorAll('.manage-row')).map((row) => row.dataset.songId);
            fetch('{{ route('mypage.manage.songs.reorder', $artist->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ song_ids: songIds }),
            });
        }

        songList.querySelectorAll('.manage-row').forEach((row) => {
            setupInlineEdit(row);
            row.querySelector('.manage-delete-btn').addEventListener('click', () => requestDelete(row));
        });
    })();
    </script>
@endsection
