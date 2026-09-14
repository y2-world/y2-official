@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tour->title)

@section('content')
    @php
        $totalOlCount = $tourSetlists
            ->filter(fn ($model) => is_array($model->setlist) && count($model->setlist) > 0)
            ->count();
        $colClass = $totalOlCount <= 2 ? 'col-xl-9' : 'col-xl-12';
    @endphp

    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $artist->name, 'url' => route('mypage.user_artists.live', $artist->id)],
                ['label' => $tour->title],
            ]])
            <p class="database-subtitle">
                <a href="{{ route('mypage.user_artists.live', $artist->id) }}">{{ $artist->name }}</a>
            </p>
            <h1 class="database-title">{{ $tour->title }}</h1>
            <p class="database-subtitle">
                @if ($tour->date1 && $tour->date2)
                    {{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}
                @elseif ($tour->date1)
                    {{ date('Y.m.d', strtotime($tour->date1)) }}
                @endif
            </p>
        </div>
    </div>

    <div class="{{ $totalOlCount >= 3 ? 'container-fluid' : 'container' }} database-year-content">
        <div class="row justify-content-center">
            <div class="{{ $colClass }}">
                <div class="setlist" style="width: 100%;">
                    {{-- ユーザー登録曲には誰でも見られる単独の詳細ページが無いため、曲名にリンクは張らない --}}
                    @include('db_concerts._setlist_rows', ['tourSetlists' => $tourSetlists, 'songs' => $songs, 'songLinkResolver' => fn ($song) => null])
                </div>
            </div>
        </div>
    </div>

    <div class="container database-year-content" style="padding-top: 0;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                    @if ($previous)
                        <a href="{{ route('mypage.user_concerts.show', $previous->id) }}" rel="prev"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if ($next)
                        <a href="{{ route('mypage.user_concerts.show', $next->id) }}" rel="next"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            Next
                            <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.setlist-row').forEach(function (row) {
        if (row.scrollWidth > row.clientWidth) {
            row.style.justifyContent = 'flex-start';
        }
        var areas = row.querySelectorAll('.setlist-subtitle-area');
        var maxH = 0;
        areas.forEach(function (a) { a.style.height = 'auto'; maxH = Math.max(maxH, a.scrollHeight); });
        areas.forEach(function (a) { a.style.height = maxH + 'px'; });
    });
});
</script>
@endsection
