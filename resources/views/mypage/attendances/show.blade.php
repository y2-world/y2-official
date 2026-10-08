@extends('layouts.app')
@section('title', 'Yuki Official - ' . ($tour->title ?? 'セットリスト'))

@section('content')
    <div class="database-hero database-hero--detail has-hero-edit @if ($errors->any()) is-hero-editing @endif">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $tour->title ?? 'セットリスト'],
            ]])
            <p class="database-subtitle" style="">
                {{-- type=0（ツアー）・1（単発ライブ）以外は複数アーティスト出演のフェス等のため、単独アーティスト名は表示しない --}}
                @if ($artist && !in_array((int) $tour->type, [2, 3, 4], true))
                    @php
                        // アーティスト名は常に自分の参加記録一覧（フィルタ済み）へのリンクにする。
                        $artistLink = route('mypage.attendances.index', ['artist_id' => ($isOfficial ? 'official-' : 'user-') . $artist->id]);
                    @endphp
                    <a href="{{ $artistLink }}" style="color: white; text-decoration: none;">
                        {{ $artist->name }}
                    </a>
                @endif
            </p>
            @php
                // ツアー名からライブのページへ（公式は Database、ユーザー登録はそのライブのページ）
                $tourLink = $tour ? ($isOfficial ? route('live.show', $tour->id) : route('mypage.user_concerts.show', $tour->id)) : null;
            @endphp
            <h1 class="database-title" style="">@if ($tourLink)<a href="{{ $tourLink }}" style="color: inherit; text-decoration: none;">{{ $tour->title }}</a>@else{{ $tour->title ?? '' }}@endif</h1>
            <p class="database-subtitle" id="attendanceDisplay" style="@if ($errors->any()) display: none; @endif">
                @if ($attendance->attended_date)
                    {{ $attendance->attended_date->format('Y.m.d') }}
                @endif
                <br>
                {{ $attendance->venue }}
            </p>
            @if ($isOwner)
                {{-- 参加日・会場の編集。アーティストの管理画面と同じく、押すと入力欄を出して「保存」に変わり、もう一度押すと保存する --}}
                <div class="hero-edit-actions @if ($errors->any()) is-editing @endif">
                    <a href="#" id="attendanceEditToggle" class="hero-edit-btn" onclick="event.preventDefault(); attendanceEditButtonPressed();">{{ $errors->any() ? '保存' : '編集' }}</a>
                    {{-- 編集中だけ、「保存」の隣に出す --}}
                    <a href="#" id="attendanceDeleteButton" class="hero-edit-btn" onclick="event.preventDefault(); if (confirm('このセットリストを削除しますか？')) { document.getElementById('attendanceDeleteForm').submit(); }" @unless ($errors->any()) hidden @endunless>削除</a>
                </div>
            @endif
            @unless ($isOwner)
                <p class="database-subtitle" style="text-align: center;">
                    by <a href="{{ route('mypage.users.stats', $attendance->external_user_id) }}" style="color: white; text-decoration: underline;">{{ $attendance->externalUser->name ?: 'ゲスト' }}</a>
                </p>
            @endunless

            @if ($isOwner)
                @if ($errors->any())
                    <div class="alert alert-danger" style="max-width: 400px; margin: 10px auto 0; text-align: center;">
                        <ul style="margin-bottom: 0; padding-left: 20px; text-align: left;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('mypage.attendances.update', $attendance) }}" id="attendanceEditForm" style="{{ $errors->any() ? 'display: block;' : 'display: none;' }} text-align: center;">
                    @csrf
                    @method('PUT')
                    <div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: center;">
                        <input type="date" name="attended_date" value="{{ old('attended_date', $attendance->attended_date?->format('Y-m-d')) }}" style="border-radius: 6px; border: none; padding: 4px 8px; font-size: 0.85rem;" required>
                        <input type="text" name="venue" value="{{ old('venue', $attendance->venue) }}" placeholder="会場" style="border-radius: 6px; border: none; padding: 4px 8px; font-size: 0.85rem;" required>
                    </div>
                    {{-- 保存せずに閉じる（ツアーの編集フォームと同じ×を、下の真ん中に） --}}
                    <div style="height: 26px; margin-top: 14px; display: flex; align-items: center; justify-content: center;">
                        <button type="button" style="background: none; border: none; color: white; cursor: pointer; padding: 0 4px; line-height: 1;" title="閉じる" onclick="toggleAttendanceEdit(false);">
                            <span class="close-x-thin" style="font-size: 18px;"></span>
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('mypage.attendances.destroy', $attendance) }}" id="attendanceDeleteForm" style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>

                <script>
                // 編集中はボタンを「保存」にし、閉じたら「編集」に戻す
                function toggleAttendanceEdit(editing) {
                    document.getElementById('attendanceDisplay').style.display = editing ? 'none' : '';
                    document.getElementById('attendanceEditForm').style.display = editing ? 'block' : 'none';
                    document.getElementById('attendanceEditToggle').textContent = editing ? '保存' : '編集';
                    document.getElementById('attendanceDeleteButton').hidden = !editing;
                    document.querySelector('.hero-edit-actions').classList.toggle('is-editing', editing);
                    document.querySelector('.database-hero').classList.toggle('is-hero-editing', editing);
                }
                function attendanceEditButtonPressed() {
                    const form = document.getElementById('attendanceEditForm');
                    if (form.style.display === 'block') {
                        form.requestSubmit();
                    } else {
                        toggleAttendanceEdit(true);
                    }
                }
                </script>
            @endif
        </div>
    </div>

    <div class="container database-year-content">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                @if ($attendance->is_planned)
                    {{-- 参加予定：セットリストはまだ無い。公演日より前は「参加予定」、公演日以降は本人に「セットリストを追加」を出す --}}
                    <div style="text-align: center; padding: 30px 0 10px;">
                        @if ($isOwner && $attendance->can_add_setlist)
                            <a href="{{ route('mypage.attendances.add_setlist', $attendance) }}" class="mypage-add-row" style="text-decoration: none;">
                                <span class="mypage-add-row-label">セットリストを追加</span>
                                <span class="mypage-add-button"><i class="fas fa-plus"></i></span>
                            </a>
                        @else
                            <p style="color: #718096; margin-bottom: 0;"><i class="fa-solid fa-calendar-plus" style="margin-right: 6px;"></i>参加予定</p>
                        @endif
                    </div>
                @else
                    <div class="setlist" style="width: 100%;">
                        @include('mypage.attendances._setlist_cards', ['setlistModel' => $isOfficial ? $attendance->dbSetlist : $attendance->userSetlist, 'songs' => $songs, 'kind' => $isOfficial ? 'official' : 'user', 'isFromTimeline' => $isFromTimeline, 'selectedDailySongs' => $attendance->selected_daily_songs ?? []])
                    </div>
                @endif

                <div class="timeline-card timeline-card--plain" style="margin-top: 40px;">
                    <div class="timeline-card-footer" style="margin: 20px 0;">
                        <div class="timeline-card-footer-left">
                            <div class="timeline-rating" data-update-url="{{ route('mypage.timeline.update', $attendance) }}">
                                <div class="timeline-stars timeline-stars-display {{ $isOwner ? 'is-editable' : '' }}" data-rating="{{ $attendance->rating ?? 0 }}">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star {{ $i <= ($attendance->rating ?? 0) ? 'is-filled' : '' }}"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <span class="timeline-card-posted-at">{{ $attendance->created_at->format('Y.m.d H:i') }}</span>
                    </div>

                    <div class="timeline-comments">
                        @forelse ($attendance->comments as $comment)
                            <div class="timeline-comment-item" data-comment-id="{{ $comment->id }}">
                                <span class="timeline-comment-user">{{ $comment->externalUser->name ?: 'ゲスト' }}</span>
                                <span class="timeline-comment-body">{{ $comment->body }}</span>
                                <textarea class="form-control timeline-comment-body-input" maxlength="1000" rows="1" hidden>{{ $comment->body }}</textarea>
                                @if ($comment->external_user_id === Auth::guard('external')->id())
                                    <button type="button" class="timeline-comment-edit" data-update-url="{{ route('mypage.timeline.comments.update', $comment) }}" title="編集">
                                        <i class="fa-solid fa-pen"></i>
                                        <i class="fa-solid fa-check" hidden></i>
                                    </button>
                                    <button type="button" class="timeline-comment-delete" data-delete-url="{{ route('mypage.timeline.comments.destroy', $comment) }}" title="削除">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p class="timeline-no-comments">まだコメントがありません。</p>
                        @endforelse
                    </div>

                    <form class="timeline-comment-form" data-post-url="{{ route('mypage.timeline.comments.store', $attendance) }}">
                        <textarea class="form-control timeline-comment-form-input" placeholder="コメント" maxlength="1000" rows="5"></textarea>
                        <button type="submit" class="timeline-comment-form-submit">送信</button>
                    </form>
                </div>

                {{-- 前後リンク --}}
                @if (!empty($previous) || !empty($next))
                    <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                        @if (!empty($previous))
                            <a href="{{ route('mypage.attendances.show', ['attendance' => $previous->id] + (request('from') ? ['from' => request('from')] : [])) }}" rel="prev"
                               style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                                <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                                Previous
                            </a>
                        @else
                            <div></div>
                        @endif
                        @if (!empty($next))
                            <a href="{{ route('mypage.attendances.show', ['attendance' => $next->id] + (request('from') ? ['from' => request('from')] : [])) }}" rel="next"
                               style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                                Next
                                <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="container database-year-content" style="padding-top: 0;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="{{ $backUrl }}" style="color: #888; font-size: 0.9rem;">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // initTimelineCardはlayouts/app.blade.php側で定義され、@yield('content')より後に
    // レンダリングされるため、DOMContentLoadedまで遅らせて確実に定義済みの状態で呼び出す。
    document.addEventListener('DOMContentLoaded', function () {
        initTimelineCard(document.querySelector('.timeline-card--plain'));
    });
    </script>
@endsection
