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
                @if ($filterUser)
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h1 class="database-title" style="font-size: 1.4rem;">{{ $filterUser->name ?: 'ゲスト' }}の投稿</h1>
                        <a href="{{ route('mypage.timeline.index') }}" class="back-link" style="font-size: 0.9rem; color: #999;">
                            <i class="fa-solid fa-xmark"></i> 絞り込みを解除
                        </a>
                    </div>
                @endif
                <div id="timelineList">
                    @foreach ($attendances as $attendance)
                        @php
                            $tour = $attendance->attendedTour;
                            // 公式アーティストはDatabase側のライブ一覧へ、ユーザー登録アーティストは
                            // 対応するデータベース的な閲覧ページ（誰でも見られる、全ツアーが並ぶ一覧）へ遷移する。
                            $artistNavUrl = $attendance->db_setlist_id
                                ? route('database.live', $tour?->artist_id)
                                : route('mypage.user_artists.live', $tour?->user_artist_id);
                            $isOwner = $attendance->external_user_id === \Illuminate\Support\Facades\Auth::guard('external')->id();
                        @endphp
                        <a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'timeline']) }}" class="timeline-card" data-attendance-id="{{ $attendance->id }}">
                            <div class="timeline-card-header">
                                <div class="timeline-card-meta">
                                    <span class="timeline-card-user" data-nav-url="{{ route('mypage.users.stats', $attendance->external_user_id) }}">{{ $attendance->externalUser->name ?: 'ゲスト' }}</span>
                                    @if ($tour?->artist)
                                        <span class="timeline-card-artist" data-nav-url="{{ $artistNavUrl }}">{{ $tour->artist->name }}</span>
                                    @endif
                                    <span class="timeline-card-title">{{ $tour->title ?? '-' }}</span>
                                    <span class="timeline-card-sub">
                                        {{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}
                                        @if ($attendance->venue)
                                            ・{{ $attendance->venue }}
                                        @endif
                                    </span>
                                </div>
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

                <p id="noAttendancesMessage" style="text-align: center; color: #999;" @if ($attendances->isNotEmpty()) hidden @endif>まだ参加したライブが記録されていません。</p>
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
    }
    .timeline-card-user {
        display: inline-block;
        color: #333;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
    }
    .timeline-card-artist {
        display: inline-block;
        color: #764ba2;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
    }
    .timeline-card-title {
        display: block;
        color: #333;
        font-size: 1.1rem;
        font-weight: 600;
    }
    .timeline-card-sub {
        color: #999;
        font-size: 0.8rem;
    }
    </style>

    <script>
    // initTimelineCardはlayouts/app.blade.php側の<script>で定義されるが、
    // そちらは@yield('content')より後にレンダリングされるため、ここでの即時実行では
    // 未定義エラーになる。DOMContentLoadedまで遅らせて確実に定義済みの状態で呼び出す。
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.timeline-card').forEach(initTimelineCard);
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
