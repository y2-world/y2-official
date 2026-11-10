@extends('layouts.app')

@section('title', 'Timeline - My Page')
@section('og_title', 'Timeline - Yuki Official')

@section('content')
    <div id="timelinePullIndicator" class="timeline-pull-indicator">
        <i class="fa-solid fa-arrow-down timeline-pull-arrow"></i>
        <span class="timeline-pull-spinner"></span>
    </div>

    <div class="container database-content" style="padding-top: 24px;">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @php
                    $myId = \Illuminate\Support\Facades\Auth::guard('external')->id();
                @endphp
                {{-- 1行目：左にプルダウン、右に「参加記録を追加 ＋」。2行目：左にチェック（スマホの幅でも重ならないように行を分ける）。
                     timeline-filter-row はほかのページでは中央寄せのまま --}}
                <div class="timeline-filter-row" style="justify-content: space-between; margin-bottom: 8px;">
                    <select class="timeline-user-select" onchange="if (this.value) window.location.href=this.value;">
                        @php $isMine = $filterUser && $filterUser->id === $myId; @endphp
                        <option value="{{ route('mypage.timeline.index', ['user_id' => 'all']) }}" {{ !$filterUser && !$commentsOnly ? 'selected' : '' }}>すべての投稿</option>
                        <option value="{{ route('mypage.timeline.index', ['user_id' => $myId]) }}" {{ $isMine && !$commentsOnly ? 'selected' : '' }}>自分の投稿</option>
                        <option value="{{ route('mypage.timeline.index', ['user_id' => 'all', 'show' => 'comments']) }}" {{ !$filterUser && $commentsOnly ? 'selected' : '' }}>すべてのコメント</option>
                        <option value="{{ route('mypage.timeline.index', ['user_id' => $myId, 'show' => 'comments']) }}" {{ $isMine && $commentsOnly ? 'selected' : '' }}>自分のコメント</option>
                    </select>
                    {{-- 参加記録（セットリスト）の追加。プルダウンの行の右端に「参加記録を追加 ＋」 --}}
                    <a href="{{ route('mypage.attendances.create') }}" class="mypage-add-row timeline-add-row" title="参加記録を追加">
                        <span class="mypage-add-row-label">参加記録を追加</span>
                        <span class="mypage-add-button"><i class="fas fa-plus"></i></span>
                    </a>
                </div>
                {{-- 参加予定（セットリストをまだ選んでいない投稿）を隠す。選んだ状態はブラウザに覚えておく --}}
                <div style="display: flex; justify-content: flex-start; align-items: center; margin: 0 0 12px;">
                    @unless ($commentsOnly)
                    {{-- 参加予定・コメントを隠すチェック。2つを横に並べる。コメントだけを出しているときは使わないので出さない --}}
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; justify-content: flex-start;">
                        <label class="unique-tour-label" style="margin: 0;">
                            <input type="checkbox" id="timelineExcludePlanned" class="unique-tour-checkbox">
                            <span class="unique-tour-text">参加予定を除く</span>
                        </label>
                        <label class="unique-tour-label" style="margin: 0;">
                            <input type="checkbox" id="timelineExcludeComments" class="unique-tour-checkbox">
                            <span class="unique-tour-text">コメントを除く</span>
                        </label>
                    </div>
                    @endunless
                </div>
                <div id="timelineList">
                    @foreach ($items as $item)
                        @if ($item['type'] === 'comment')
                            {{-- コメント：書いた人・コメント・どの投稿へのコメントか。押すとその投稿を開く --}}
                            @php
                                $comment = $item['model'];
                                $target = $comment->attendance;
                                $targetTour = $target->attendedTour;
                            @endphp
                            <a href="{{ route('mypage.attendances.show', ['attendance' => $target, 'from' => 'timeline']) }}" class="timeline-card timeline-comment-card">
                                <div class="timeline-card-meta">
                                    <span class="timeline-card-user" data-nav-url="{{ route('mypage.users.stats', $comment->external_user_id) }}">
                                        @if ($comment->externalUser?->avatar_url)
                                            <img src="{{ $comment->externalUser->avatar_url }}" alt="" class="timeline-card-avatar">
                                        @else
                                            <span class="timeline-card-avatar timeline-card-avatar--placeholder"><i class="fa-solid fa-user"></i></span>
                                        @endif{{ $comment->externalUser?->name ?: 'ゲスト' }}</span>
                                    <span class="timeline-comment-card-label"><i class="fa-regular fa-comment"></i> {{ $target->externalUser?->name ?: 'ゲスト' }}さんの投稿にコメント</span>
                                </div>
                                {{-- 参加予定の投稿へのコメントも、投稿のカードと同じく右上に「参加予定」のバッジ --}}
                                @if ($target->is_planned)
                                    <span class="timeline-planned-badge">参加予定</span>
                                @endif
                                <p class="timeline-comment-card-body">{{ $comment->body }}</p>
                                <div class="timeline-comment-card-target">
                                    @if ($targetTour?->artist)
                                        <span class="timeline-card-artist">{{ $targetTour->artist->name }}</span>
                                    @endif
                                    <span class="timeline-comment-card-title">{{ $targetTour->title ?? '-' }}</span>
                                    <span class="timeline-card-sub">
                                        @if ($target->venue)
                                            {{ $target->venue }} ・
                                        @endif
                                        {{ $target->attended_date?->format('Y.m.d') ?? '-' }}
                                    </span>
                                </div>
                                <div class="timeline-card-footer" style="justify-content: flex-end;">
                                    <span class="timeline-card-posted-at">{{ $comment->created_at->format('Y.m.d H:i') }}</span>
                                </div>
                            </a>
                            @continue
                        @endif
                        @php
                            $attendance = $item['model'];
                            $tour = $attendance->attendedTour;
                            // アーティスト名は常に自分の参加記録一覧（フィルタ済み）へ遷移する。
                            $artistNavUrl = route('mypage.attendances.index', [
                                'artist_id' => $attendance->is_official
                                    ? 'official-' . $tour?->artist_id
                                    : 'user-' . $tour?->user_artist_id,
                            ]);
                            $isOwner = $attendance->external_user_id === \Illuminate\Support\Facades\Auth::guard('external')->id();
                        @endphp
                        <a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'timeline']) }}" class="timeline-card{{ $attendance->is_planned ? ' is-planned' : '' }}" data-attendance-id="{{ $attendance->id }}">
                            <div class="timeline-card-header">
                                <div class="timeline-card-meta">
                                    {{-- ユーザー名の左にユーザーの画像（無ければ人のアイコン） --}}
                                    <span class="timeline-card-user" data-nav-url="{{ route('mypage.users.stats', $attendance->external_user_id) }}">
                                        @if ($attendance->externalUser->avatar_url)
                                            <img src="{{ $attendance->externalUser->avatar_url }}" alt="" class="timeline-card-avatar">
                                        @else
                                            <span class="timeline-card-avatar timeline-card-avatar--placeholder"><i class="fa-solid fa-user"></i></span>
                                        @endif{{ $attendance->externalUser->name ?: 'ゲスト' }}</span>
                                    @if ($tour?->artist)
                                        <span class="timeline-card-artist" data-nav-url="{{ $artistNavUrl }}">{{ $tour->artist->name }}</span>
                                    @endif
                                    <span class="timeline-card-title">{{ $tour->title ?? '-' }}</span>
                                    <span class="timeline-card-sub">
                                        @if ($attendance->venue)
                                            {{ $attendance->venue }} ・
                                        @endif
                                        {{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}
                                    </span>
                                </div>
                                {{-- セットリストをまだ選んでいない参加予定の投稿は、右上に「参加予定」のバッジ。
                                     公演日以降は、本人には代わりにセットリストを追加する「＋」を出す --}}
                                @if ($attendance->is_planned)
                                    @if ($isOwner && $attendance->can_add_setlist)
                                        <span class="mypage-add-row timeline-planned-add" title="セットリストを追加" data-nav-url="{{ route('mypage.attendances.add_setlist', $attendance) }}">
                                            <span class="mypage-add-row-label">セットリストを追加</span>
                                            <span class="mypage-add-button"><i class="fas fa-plus"></i></span>
                                        </span>
                                    @else
                                        <span class="timeline-planned-badge">参加予定</span>
                                    @endif
                                @endif
                            </div>

                            <div class="timeline-card-footer">
                                <div class="timeline-card-footer-left">
                                    <div class="timeline-rating" data-update-url="{{ route('mypage.timeline.update', $attendance) }}">
                                        <div class="timeline-stars timeline-stars-display {{ $isOwner ? 'is-editable' : '' }}" data-rating="{{ $attendance->rating ?? 0 }}">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="fa-solid fa-star {{ $i <= ($attendance->rating ?? 0) ? 'is-filled' : '' }}"></i>
                                            @endfor
                                        </div>
                                    </div>

                                    <span class="timeline-card-comment-count">
                                        <i class="fa-regular fa-comment"></i> {{ $attendance->comments->count() }}
                                    </span>
                                </div>

                                <span class="timeline-card-posted-at">{{ $attendance->created_at->format('Y.m.d H:i') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <p id="noAttendancesMessage" style="text-align: center; color: #999;" @if ($items->isNotEmpty()) hidden @endif>まだ参加したライブが記録されていません。</p>
            </div>
        </div>
    </div>

    <style>
    .timeline-pull-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 0;
        overflow: hidden;
        color: #999;
        transition: height 0.15s ease;
    }
    .timeline-pull-indicator.is-visible {
        height: 40px;
    }
    .timeline-pull-spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 2px solid #ddd;
        border-top-color: #764ba2;
        border-radius: 50%;
        animation: timeline-spin 0.6s linear infinite;
    }
    .timeline-pull-indicator.is-spinning .timeline-pull-arrow {
        display: none;
    }
    .timeline-pull-indicator.is-spinning .timeline-pull-spinner {
        display: inline-block;
    }
    @keyframes timeline-spin {
        to { transform: rotate(360deg); }
    }
    .timeline-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .timeline-card-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
        min-width: 0;
        /* カードの幅いっぱいに取る。中身の幅で縮むと、右上のバッジ・「セットリストを追加 ＋」の分を引いたユーザー名の幅（下の max-width）が
           ツアー名の短いカードで狭くなりすぎ、短い名前でも2行になる */
        flex: 1 1 auto;
    }
    /* 画像と名前の高さをそろえる */
    .timeline-card-user {
        display: inline-flex;
        align-items: center;
        color: #333;
        font-size: 0.9rem;
        font-weight: 400;
        cursor: pointer;
    }
    .timeline-card-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        margin-right: 6px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        /* サイト全体の画像の影（img の box-shadow）は付けない */
        box-shadow: none;
    }
    .timeline-card-avatar--placeholder {
        background: #ede7f6;
        color: #764ba2;
        font-size: 0.65rem;
    }
    .timeline-card-artist {
        display: inline-block;
        color: #764ba2;
        font-size: 0.85rem;
        font-weight: 400;
        cursor: pointer;
    }
    .timeline-card-title {
        display: block;
        color: #333;
        font-size: 1.1rem;
        font-weight: 400;
    }
    .timeline-card-sub {
        color: #999;
        font-size: 0.8rem;
    }
    /* コメントのカード：コメントの本文を大きく、どの投稿へのコメントかを下に小さく */
    .timeline-comment-card-label {
        color: #999;
        font-size: 0.75rem;
    }
    .timeline-comment-card-body {
        margin: 8px 0 10px;
        color: #333;
        font-size: 0.85rem;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .timeline-comment-card-target {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
        padding-left: 10px;
        border-left: 3px solid #ede7f6;
    }
    .timeline-comment-card-title {
        color: #333;
        font-size: 0.9rem;
    }
    /* セットリストをまだ選んでいない参加予定の投稿のバッジ */
    /* 見出しの横に列を取るとタイトルの幅が狭くなるので、カードの右上に重ねて置く */
    .timeline-planned-badge {
        position: absolute;
        top: 14px;
        right: 14px;
        padding: 2px 10px;
        border-radius: 10px;
        background: #ede7f6;
        color: #764ba2;
        font-size: 0.7rem;
        white-space: nowrap;
    }
    /* いちばん上の「参加記録を追加 ＋」（右端） */
    /* プルダウンの行の右端の「参加記録を追加 ＋」 */
    .timeline-add-row {
        flex-shrink: 0;
        text-decoration: none;
    }
    /* 公演日以降の参加予定（本人）：バッジの代わりに右上に出す「セットリストを追加 ＋」 */
    .timeline-planned-add {
        position: absolute;
        top: 12px;
        right: 12px;
        gap: 6px;
        cursor: pointer;
    }
    .timeline-planned-add .mypage-add-row-label {
        font-size: 0.75rem;
    }
    .timeline-planned-add .mypage-add-button {
        width: 30px;
        height: 30px;
        font-size: 0.8rem;
    }
    /* バッジと重なるのはいちばん上のユーザー名の行だけなので、その行だけバッジの手前で折り返す */
    .timeline-card.is-planned .timeline-card-user {
        max-width: calc(100% - 72px);
    }
    /* 「セットリストを追加 ＋」は幅があるので、その分だけ手前で折り返す */
    .timeline-card.is-planned:has(.timeline-planned-add) .timeline-card-user {
        max-width: calc(100% - 150px);
    }
    </style>

    <script>
    // 「参加予定を除く」「コメントを除く」：チェックした種類のカードを隠す。チェックの状態はブラウザに覚えておく
    (function () {
        [
            { id: 'timelineExcludePlanned', selector: '.timeline-card.is-planned' },
            { id: 'timelineExcludeComments', selector: '.timeline-card.timeline-comment-card' },
        ].forEach(function (filter) {
            var box = document.getElementById(filter.id);
            if (!box) return;
            var apply = function () {
                document.querySelectorAll(filter.selector).forEach(function (card) { card.style.display = box.checked ? 'none' : ''; });
            };
            try { box.checked = localStorage.getItem(filter.id) === '1'; } catch (e) {}
            apply();
            box.addEventListener('change', function () {
                try { localStorage.setItem(filter.id, box.checked ? '1' : '0'); } catch (e) {}
                apply();
            });
        });
    })();
    </script>

    <script>
    // initTimelineCardはlayouts/app.blade.php側の<script>で定義されるが、
    // そちらは@yield('content')より後にレンダリングされるため、ここでの即時実行では
    // 未定義エラーになる。DOMContentLoadedまで遅らせて確実に定義済みの状態で呼び出す。
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.timeline-card:not(.timeline-comment-card)').forEach(initTimelineCard);
        // コメントのカードには星やコメント欄が無いので、書いた人の名前（別ページへ飛ぶ）だけ
        document.querySelectorAll('.timeline-comment-card [data-nav-url]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                window.location.href = el.dataset.navUrl;
            });
        });
    });

    // ブラウザのbfcacheから復元された場合（投稿詳細でコメントを追加後に「戻る」した時など）、
    // コメント数などがページ離脱時点のまま古くなっているため強制的に再読み込みする。
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            location.reload();
        }
    });

    // --- Pull to Refresh（ページ先頭で下にスワイプするとタイムラインを再読み込みする） ---
    (function () {
        const indicator = document.getElementById('timelinePullIndicator');
        let startY = 0;
        let pulling = false;

        window.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
                pulling = true;
            }
        }, { passive: true });

        window.addEventListener('touchmove', (e) => {
            if (!pulling) return;
            const diff = e.touches[0].clientY - startY;
            if (diff > 0 && window.scrollY === 0) {
                indicator.classList.add('is-visible');
            }
        }, { passive: true });

        window.addEventListener('touchend', (e) => {
            if (!pulling) return;
            pulling = false;
            if (indicator.classList.contains('is-visible')) {
                indicator.classList.add('is-spinning');
                location.reload();
            } else {
                indicator.classList.remove('is-visible');
            }
        });
    })();
    </script>
@endsection
