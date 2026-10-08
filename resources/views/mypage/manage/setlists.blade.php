@extends('layouts.app')

@section('title', $concert->title . ' セットリストを管理 - Manage My Artists & Setlists')
@section('og_title', $concert->title . ' セットリストを管理 - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail manage-page has-hero-edit">
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
            {{-- ツアー情報（ツアー名・開催期間・SCHEDULE）の編集 --}}
            <button type="button" id="concertInfoEditToggle" class="hero-edit-btn">編集</button>
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
                    <div class="mb-3">
                            <label for="concert_schedule" class="form-label">SCHEDULE</label>
                            {{-- 公式のツアーと同じ書き方。参加登録で、参加日をこの公演から選べるようになる --}}
                            <textarea class="form-control" id="concert_schedule" name="schedule" rows="5" placeholder="2026/10/8(水) 日本武道館&#10;2026/10/9(木) 日本武道館">{{ $concert->schedule }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">保存</button>
                    {{-- ツアーの追加フォームと同じ×で閉じる（保存はしない） --}}
                    <div style="text-align: center; margin-top: 8px;">
                        <button type="button" id="concertInfoClose" style="background: none; border: none; color: #999; cursor: pointer; padding: 20px;" title="閉じる">
                            <span class="close-x-thin" style="font-size: 30px;"></span>
                        </button>
                    </div>
                </form>

                {{-- ツアー情報を編集している間は、パターンの一覧と追加ボタンを隠す --}}
                <div id="setlistPatternsSection">
                <div id="setlistList">
                @foreach ($setlists as $index => $setlist)
                    <div class="manage-row" style="display: block;" data-setlist-id="{{ $setlist->id }}">
                        <div class="setlist-pattern-summary">
                            {{-- ここをつかんでドラッグでパターンを並べ替える（スマホは少し長押ししてから） --}}
                            <span class="manage-drag-handle setlist-pattern-drag-handle" title="ドラッグで並べ替え"><i class="fa-solid fa-grip-lines"></i></span>
                            <span class="select-card-icon"><i class="fa-solid fa-music"></i></span>
                            <a href="{{ route('mypage.manage.setlists.edit', [$artist->id, $concert->id, $setlist->id]) }}" class="manage-row-title" style="color: inherit; text-decoration: none;">
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
                                <span class="setlist-pattern-title-display" data-title="{{ $setlist->subtitle }}" @if (!$setlist->subtitle) hidden @endif>{!! $subtitleHtml !!}</span>
                                @php
                                    $songCount = count($setlist->setlist ?? []) + count($setlist->encore ?? []);
                                @endphp
                                <span class="setlist-pattern-song-count" style="color: #999; font-size: 0.85rem; display: block;">{{ $songCount }}曲</span>
                            </a>
                            <div class="setlist-pattern-actions">
                                <button type="button" class="manage-edit-btn setlist-pattern-duplicate" data-duplicate-url="{{ route('mypage.manage.setlists.duplicate', [$artist->id, $concert->id, $setlist->id]) }}" title="複製">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                                {{-- ほかの一覧のカードと同じく「＞」で、そのパターンの編集ページへ --}}
                                <a href="{{ route('mypage.manage.setlists.edit', [$artist->id, $concert->id, $setlist->id]) }}" class="setlist-pattern-open" title="編集">
                                    <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>

                <p id="noSetlistsMessage" style="text-align: center; color: #999; margin-top: 0;" @if ($setlists->isNotEmpty()) hidden @endif>まだセットリストパターンがありません。</p>

                <div class="mypage-add-row" style="margin-top: 24px;">
                    <span class="mypage-add-row-label">パターンを追加</span>
                    <a href="{{ route('mypage.manage.setlists.create', [$artist->id, $concert->id]) }}" id="newSetlistPatternBtn" class="mypage-add-button" title="セットリストパターンを追加">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .setlist-pattern-summary {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .setlist-pattern-open {
        color: inherit;
        text-decoration: none;
        padding: 4px 2px 4px 6px;
    }
    .setlist-pattern-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    </style>

    @include('mypage.manage._song_rows_sortable')
    <script>

    // ツアー情報の編集フォームを開いている間は、ほかのもの（パターンの一覧・追加ボタン）を隠す
    function setConcertInfoEditing(editing) {
        document.getElementById('concertInfoForm').hidden = !editing;
        document.getElementById('setlistPatternsSection').hidden = editing;
        document.getElementById('concertInfoEditToggle').hidden = editing;
        if (editing) {
            document.getElementById('concertInfoForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    document.getElementById('concertInfoEditToggle').addEventListener('click', () => {
        setConcertInfoEditing(document.getElementById('concertInfoForm').hidden);
    });
    document.getElementById('concertInfoClose').addEventListener('click', () => setConcertInfoEditing(false));

    // パターンの複製：サーバー側で曲目をコピーした新しいパターンを作り、その編集ページを開く
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

    // パターン自体（セットリストパターンの表示順）を、曲の並べ替えと同じくドラッグで並べ替える。
    // 「パターンN」フォールバック表示はサーバー側で順序に応じて算出されるため、
    // ここではDOM上の並び替えとorder_noの保存だけ行い、番号表示はユーザーが
    // 手動でページを再読み込みした時点で正しい値になる。
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

    makeSongRowsSortable(document.getElementById('setlistList'), persistSetlistOrder, {
        draggable: '[data-setlist-id]',
        handle: '.setlist-pattern-drag-handle',
    });

    </script>
@endsection
